<?php

namespace App\Games\Xiangqi;

final class Rules
{
    public static function isSquareAttacked(Board $board, Position $target, Side $bySide): bool
    {
        foreach ($board->piecesOf($bySide) as [$pos, $piece]) {
            foreach (PieceMoves::pseudoLegalMoves($board, $pos) as $candidate) {
                if ($candidate->equals($target)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * True if both generals face each other on the same column with no piece
     * between them. Neither player may make a move that results in this
     * position ("flying general" / "generals may not see each other").
     */
    public static function violatesFlyingGeneralRule(Board $board): bool
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

    public static function isGeneralInCheck(Board $board, Side $side): bool
    {
        $general = $board->findGeneral($side);
        if ($general === null) {
            // No general on the board for this side to endanger (only reachable
            // via constructed/test positions - real play always ends at checkmate
            // before a general could be captured).
            return false;
        }

        return self::isSquareAttacked($board, $general, $side->opponent())
            || self::violatesFlyingGeneralRule($board);
    }

    /** @return Move[] */
    public static function getLegalMoves(Board $board, Position $pos): array
    {
        $piece = $board->get($pos);
        if ($piece === null) {
            return [];
        }

        $legal = [];
        foreach (PieceMoves::pseudoLegalMoves($board, $pos) as $target) {
            $simulated = $board->clone();
            $captured = $simulated->get($target);
            $simulated->set($target, $piece);
            $simulated->set($pos, null);

            if (self::isGeneralInCheck($simulated, $piece->side)) {
                continue;
            }

            $legal[] = new Move($pos, $target, $piece, $captured);
        }

        return $legal;
    }

    /** @return Move[] */
    public static function getAllLegalMoves(Board $board, Side $side): array
    {
        $moves = [];
        foreach ($board->piecesOf($side) as [$pos, $piece]) {
            array_push($moves, ...self::getLegalMoves($board, $pos));
        }

        return $moves;
    }
}
