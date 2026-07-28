<?php

namespace App\Games\Xiangqi\Ai;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\PieceMoves;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Side;

/**
 * Cheap static position evaluation for the AI's search - material plus a
 * pseudo-mobility term. Deliberately uses PieceMoves::pseudoLegalMoves()
 * (not Rules::getLegalMoves()) for mobility: the fully-legal move list also
 * simulates a self-check test per candidate move, which is far too
 * expensive to run at every leaf node of a minimax search.
 */
final class Evaluator
{
    private const VALUES = [
        'chariot' => 90,
        'cannon' => 45,
        'horse' => 40,
        'advisor' => 20,
        'elephant' => 20,
        'soldier' => 10,
        'general' => 0,
    ];

    public static function evaluate(Board $board, Side $side): int
    {
        $material = self::materialFor($board, $side) - self::materialFor($board, $side->opponent());
        $mobility = self::mobilityFor($board, $side) - self::mobilityFor($board, $side->opponent());

        return $material + $mobility;
    }

    private static function materialFor(Board $board, Side $side): int
    {
        $total = 0;

        foreach ($board->piecesOf($side) as [$pos, $piece]) {
            $value = self::VALUES[$piece->type->value];

            if ($piece->type === PieceType::Soldier) {
                $crossed = $side === Side::Red ? $pos->y >= 5 : $pos->y <= 4;
                if ($crossed) {
                    $value += 10;
                }
            }

            $total += $value;
        }

        return $total;
    }

    private static function mobilityFor(Board $board, Side $side): int
    {
        $count = 0;

        foreach ($board->piecesOf($side) as [$pos, $piece]) {
            $count += count(PieceMoves::pseudoLegalMoves($board, $pos));
        }

        return $count;
    }
}
