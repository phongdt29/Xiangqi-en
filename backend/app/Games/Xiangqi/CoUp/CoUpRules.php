<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;

final class CoUpRules
{
    public static function isSquareAttacked(CoUpBoard $board, Position $target, Side $bySide): bool
    {
        foreach ($board->piecesOf($bySide) as [$pos, $piece]) {
            foreach (CoUpPieceMoves::pseudoLegalMoves($board, $pos) as $candidate) {
                if ($candidate->equals($target)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generals are never hidden in Cờ Úp, so the flying-general rule works
     * identically to standard Xiangqi.
     */
    public static function violatesFlyingGeneralRule(CoUpBoard $board): bool
    {
        $red = $board->findGeneral(Side::Red);
        $black = $board->findGeneral(Side::Black);

        if ($red === null || $black === null || $red->x !== $black->x) {
            return false;
        }

        $x = $red->x;
        [$fromY, $toY] = $red->y < $black->y ? [$red->y, $black->y] : [$black->y, $red->y];

        for ($y = $fromY + 1; $y < $toY; $y++) {
            if ($board->get(new Position($x, $y)) !== null) {
                return false;
            }
        }

        return true;
    }

    public static function isGeneralInCheck(CoUpBoard $board, Side $side): bool
    {
        $general = $board->findGeneral($side);
        if ($general === null) {
            return false;
        }

        return self::isSquareAttacked($board, $general, $side->opponent())
            || self::violatesFlyingGeneralRule($board);
    }

    /** @return CoUpMove[] */
    public static function getLegalMoves(CoUpBoard $board, Position $pos): array
    {
        $piece = $board->get($pos);
        if ($piece === null) {
            return [];
        }

        $legal = [];
        foreach (CoUpPieceMoves::pseudoLegalMoves($board, $pos) as $target) {
            $simulated = $board->clone();
            $captured = $simulated->get($target);
            $simulated->set($target, $piece);
            $simulated->set($pos, null);

            if (self::isGeneralInCheck($simulated, $piece->side)) {
                continue;
            }

            $legal[] = new CoUpMove($pos, $target, $piece, $captured);
        }

        return $legal;
    }

    /** @return CoUpMove[] */
    public static function getAllLegalMoves(CoUpBoard $board, Side $side): array
    {
        $moves = [];
        foreach ($board->piecesOf($side) as [$pos, $piece]) {
            array_push($moves, ...self::getLegalMoves($board, $pos));
        }

        return $moves;
    }
}
