<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;

final class CoUpPieceMoves
{
    /** @return Position[] */
    public static function pseudoLegalMoves(CoUpBoard $board, Position $pos): array
    {
        $piece = $board->get($pos);
        if ($piece === null) {
            return [];
        }

        $effectiveType = $piece->revealed ? $piece->trueType : CoUpHomeLayout::typeAt($pos);

        // General is always restricted to the palace (never hidden in the
        // first place). Advisor/Elephant are restricted unless *revealed* -
        // an unrevealed piece must still follow the restricted positional
        // rule for whatever square it's masquerading as.
        $restricted = ! ($piece->revealed && in_array($effectiveType, [PieceType::Advisor, PieceType::Elephant], true));

        return match ($effectiveType) {
            PieceType::General => self::generalMoves($board, $pos, $piece),
            PieceType::Advisor => self::advisorMoves($board, $pos, $piece, $restricted),
            PieceType::Elephant => self::elephantMoves($board, $pos, $piece, $restricted),
            PieceType::Horse => self::horseMoves($board, $pos, $piece),
            PieceType::Chariot => self::slidingMoves($board, $pos, $piece),
            PieceType::Cannon => self::cannonMoves($board, $pos, $piece),
            PieceType::Soldier => self::soldierMoves($board, $pos, $piece),
            null => [],
        };
    }

    private static function inPalace(Position $pos, Side $side): bool
    {
        if ($pos->x < 3 || $pos->x > 5) {
            return false;
        }

        return $side === Side::Red ? ($pos->y <= 2) : ($pos->y >= 7);
    }

    private static function canLandOn(CoUpBoard $board, Position $pos, Side $side): bool
    {
        if (! CoUpBoard::inBounds($pos)) {
            return false;
        }

        $occupant = $board->get($pos);

        return $occupant === null || $occupant->side !== $side;
    }

    /** @return Position[] */
    private static function generalMoves(CoUpBoard $board, Position $pos, CoUpPiece $piece): array
    {
        $moves = [];
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            if (self::inPalace($target, $piece->side) && self::canLandOn($board, $target, $piece->side)) {
                $moves[] = $target;
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function advisorMoves(CoUpBoard $board, Position $pos, CoUpPiece $piece, bool $restricted): array
    {
        $moves = [];
        foreach ([[1, 1], [1, -1], [-1, 1], [-1, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            if (! CoUpBoard::inBounds($target)) {
                continue;
            }
            if ($restricted && ! self::inPalace($target, $piece->side)) {
                continue;
            }
            if (self::canLandOn($board, $target, $piece->side)) {
                $moves[] = $target;
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function elephantMoves(CoUpBoard $board, Position $pos, CoUpPiece $piece, bool $restricted): array
    {
        $moves = [];
        foreach ([[2, 2], [2, -2], [-2, 2], [-2, -2]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            if (! CoUpBoard::inBounds($target)) {
                continue;
            }

            // Unrevealed (still-restricted) Elephants can never cross the
            // river - once revealed, this special Cờ Úp rule lifts it.
            if ($restricted) {
                if ($piece->side === Side::Red && $target->y > 4) {
                    continue;
                }
                if ($piece->side === Side::Black && $target->y < 5) {
                    continue;
                }
            }

            $eye = $pos->withOffset(intdiv($dx, 2), intdiv($dy, 2));
            if ($board->get($eye) !== null) {
                continue;
            }

            if (self::canLandOn($board, $target, $piece->side)) {
                $moves[] = $target;
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function horseMoves(CoUpBoard $board, Position $pos, CoUpPiece $piece): array
    {
        $moves = [];
        $offsets = [
            [1, 2, 0, 1], [1, -2, 0, -1], [-1, 2, 0, 1], [-1, -2, 0, -1],
            [2, 1, 1, 0], [2, -1, 1, 0], [-2, 1, -1, 0], [-2, -1, -1, 0],
        ];

        foreach ($offsets as [$dx, $dy, $legDx, $legDy]) {
            $leg = $pos->withOffset($legDx, $legDy);
            if ($board->get($leg) !== null) {
                continue; // hobbled
            }

            $target = $pos->withOffset($dx, $dy);
            if (self::canLandOn($board, $target, $piece->side)) {
                $moves[] = $target;
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function slidingMoves(CoUpBoard $board, Position $pos, CoUpPiece $piece): array
    {
        $moves = [];
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            while (CoUpBoard::inBounds($target)) {
                $occupant = $board->get($target);
                if ($occupant === null) {
                    $moves[] = $target;
                } else {
                    if ($occupant->side !== $piece->side) {
                        $moves[] = $target;
                    }
                    break;
                }
                $target = $target->withOffset($dx, $dy);
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function cannonMoves(CoUpBoard $board, Position $pos, CoUpPiece $piece): array
    {
        $moves = [];
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            $screenFound = false;

            while (CoUpBoard::inBounds($target)) {
                $occupant = $board->get($target);

                if (! $screenFound) {
                    if ($occupant === null) {
                        $moves[] = $target;
                    } else {
                        $screenFound = true;
                    }
                } elseif ($occupant !== null) {
                    if ($occupant->side !== $piece->side) {
                        $moves[] = $target;
                    }
                    break;
                }

                $target = $target->withOffset($dx, $dy);
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function soldierMoves(CoUpBoard $board, Position $pos, CoUpPiece $piece): array
    {
        $forwardDy = $piece->side === Side::Red ? 1 : -1;
        $crossed = $piece->side === Side::Red ? $pos->y >= 5 : $pos->y <= 4;

        $offsets = [[0, $forwardDy]];
        if ($crossed) {
            $offsets[] = [1, 0];
            $offsets[] = [-1, 0];
        }

        $moves = [];
        foreach ($offsets as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            if (self::canLandOn($board, $target, $piece->side)) {
                $moves[] = $target;
            }
        }

        return $moves;
    }
}
