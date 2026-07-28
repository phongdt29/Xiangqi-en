<?php

namespace App\Games\Xiangqi\Ai;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\GameState;
use App\Games\Xiangqi\Move;
use App\Games\Xiangqi\Rules;
use App\Games\Xiangqi\Side;
use RuntimeException;

/**
 * Negamax with alpha-beta pruning. A wall-clock deadline is checked at every
 * node so a slow position can never hang a request - it just falls back to
 * the static evaluation early, still returning a legal (if less deeply
 * calculated) move.
 */
final class MinimaxAi
{
    private const MATE_SCORE = 1_000_000;

    /**
     * @param  int  $randomTopN  >1 makes the choice random among the top N
     *                           scored root moves - this is what makes "Easy"
     *                           beatable instead of merely shallow-but-perfect.
     */
    public static function chooseMove(GameState $state, int $maxDepth, float $timeLimitSeconds, int $randomTopN = 1): Move
    {
        $deadline = microtime(true) + $timeLimitSeconds;
        $side = $state->turn;

        $rootMoves = self::orderMoves(Rules::getAllLegalMoves($state->board, $side));
        if ($rootMoves === []) {
            throw new RuntimeException('No legal moves available for the AI to choose from.');
        }

        $scored = [];
        foreach ($rootMoves as $move) {
            $childBoard = self::applyMove($state->board, $move);
            $score = -self::search($childBoard, $side->opponent(), $maxDepth - 1, -self::MATE_SCORE * 2, self::MATE_SCORE * 2, $deadline);
            $scored[] = [$move, $score];
        }

        usort($scored, fn ($a, $b) => $b[1] <=> $a[1]);

        $topCount = max(1, min($randomTopN, count($scored)));
        $top = array_slice($scored, 0, $topCount);

        return $top[array_rand($top)][0];
    }

    private static function search(Board $board, Side $side, int $depth, int $alpha, int $beta, float $deadline): int
    {
        // Rules::getAllLegalMoves() is expensive (it simulates every
        // candidate move to check for self-check) - never call it at a leaf,
        // only when we're actually going to branch further. This means a
        // checkmate landing exactly on the search horizon just scores as a
        // material evaluation instead of a forced win - a normal, cheap
        // trade-off for depth-limited search (the classic "horizon effect").
        if ($depth <= 0 || microtime(true) >= $deadline) {
            return Evaluator::evaluate($board, $side);
        }

        $moves = Rules::getAllLegalMoves($board, $side);

        if ($moves === []) {
            // No legal move is always a loss in Xiangqi, whether it's
            // checkmate or stalemate - prefer the fastest mate / slowest loss.
            return -self::MATE_SCORE - $depth;
        }

        $best = -self::MATE_SCORE * 2;
        foreach (self::orderMoves($moves) as $move) {
            $childBoard = self::applyMove($board, $move);
            $score = -self::search($childBoard, $side->opponent(), $depth - 1, -$beta, -$alpha, $deadline);

            if ($score > $best) {
                $best = $score;
            }
            $alpha = max($alpha, $score);
            if ($alpha >= $beta) {
                break;
            }
        }

        return $best;
    }

    private static function applyMove(Board $board, Move $move): Board
    {
        $next = $board->clone();
        $next->set($move->to, $move->piece);
        $next->set($move->from, null);

        return $next;
    }

    /**
     * @param  Move[]  $moves
     * @return Move[]
     */
    private static function orderMoves(array $moves): array
    {
        usort($moves, fn (Move $a, Move $b) => ($b->captured !== null ? 1 : 0) <=> ($a->captured !== null ? 1 : 0));

        return $moves;
    }
}
