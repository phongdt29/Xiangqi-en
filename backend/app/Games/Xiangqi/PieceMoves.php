<?php

namespace App\Games\Xiangqi;

final class PieceMoves
{
    /** @return Position[] */
    public static function pseudoLegalMoves(Board $board, Position $pos): array
    {
        $piece = $board->get($pos);
        if ($piece === null) {
            return [];
        }

        return match ($piece->type) {
            PieceType::General => self::generalMoves($board, $pos, $piece),
            PieceType::Advisor => self::advisorMoves($board, $pos, $piece),
            PieceType::Elephant => self::elephantMoves($board, $pos, $piece),
            PieceType::Horse => self::horseMoves($board, $pos, $piece),
            PieceType::Chariot => self::slidingMoves($board, $pos, $piece),
            PieceType::Cannon => self::cannonMoves($board, $pos, $piece),
            PieceType::Soldier => self::soldierMoves($board, $pos, $piece),
        };
    }

    private static function inPalace(Position $pos, Side $side): bool
    {
        if ($pos->x < 3 || $pos->x > 5) {
            return false;
        }

        return $side === Side::Red ? ($pos->y <= 2) : ($pos->y >= 7);
    }

    private static function canLandOn(Board $board, Position $pos, Side $side): bool
    {
        if (! Board::inBounds($pos)) {
            return false;
        }

        $occupant = $board->get($pos);

        return $occupant === null || $occupant->side !== $side;
    }

    /** @return Position[] */
    private static function orthogonalWithin(Board $board, Position $pos, Piece $piece, callable $withinBounds): array
    {
        $moves = [];
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            if ($withinBounds($target) && self::canLandOn($board, $target, $piece->side)) {
                $moves[] = $target;
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function generalMoves(Board $board, Position $pos, Piece $piece): array
    {
        return self::orthogonalWithin($board, $pos, $piece, fn (Position $p) => self::inPalace($p, $piece->side));
    }

    /** @return Position[] */
    private static function advisorMoves(Board $board, Position $pos, Piece $piece): array
    {
        $moves = [];
        foreach ([[1, 1], [1, -1], [-1, 1], [-1, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            if (self::inPalace($target, $piece->side) && self::canLandOn($board, $target, $piece->side)) {
                $moves[] = $target;
            }
        }

        return $moves;
    }

    /** @return Position[] */
    private static function elephantMoves(Board $board, Position $pos, Piece $piece): array
    {
        $moves = [];
        foreach ([[2, 2], [2, -2], [-2, 2], [-2, -2]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            if (! Board::inBounds($target)) {
                continue;
            }

            // Elephants can never cross the river.
            if ($piece->side === Side::Red && $target->y > 4) {
                continue;
            }
            if ($piece->side === Side::Black && $target->y < 5) {
                continue;
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
    private static function horseMoves(Board $board, Position $pos, Piece $piece): array
    {
        $moves = [];
        // [dx, dy, legDx, legDy] - the "leg" square is the orthogonal step
        // in the direction of the long axis of the L-shape.
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
    private static function slidingMoves(Board $board, Position $pos, Piece $piece): array
    {
        $moves = [];
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            while (Board::inBounds($target)) {
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
    private static function cannonMoves(Board $board, Position $pos, Piece $piece): array
    {
        $moves = [];
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $target = $pos->withOffset($dx, $dy);
            $screenFound = false;

            while (Board::inBounds($target)) {
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
    private static function soldierMoves(Board $board, Position $pos, Piece $piece): array
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
