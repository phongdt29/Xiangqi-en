<?php

namespace App\Games\Xiangqi;

final class GameEngine
{
    /**
     * @throws IllegalMoveException
     */
    public static function makeMove(GameState $state, Position $from, Position $to): GameState
    {
        if ($state->status === GameStatus::Checkmate || $state->status === GameStatus::Stalemate) {
            throw new IllegalMoveException($from, $to);
        }

        $piece = $state->board->get($from);
        if ($piece === null || $piece->side !== $state->turn) {
            throw new IllegalMoveException($from, $to);
        }

        $move = null;
        foreach (Rules::getLegalMoves($state->board, $from) as $candidate) {
            if ($candidate->to->equals($to)) {
                $move = $candidate;
                break;
            }
        }

        if ($move === null) {
            throw new IllegalMoveException($from, $to);
        }

        $board = $state->board->clone();
        $board->set($move->to, $move->piece);
        $board->set($move->from, null);

        $nextTurn = $state->turn->opponent();

        return new GameState(
            board: $board,
            turn: $nextTurn,
            moveHistory: [...$state->moveHistory, $move],
            status: GameStatusResolver::resolve($board, $nextTurn),
        );
    }
}
