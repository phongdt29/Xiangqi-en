<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\IllegalMoveException;
use App\Games\Xiangqi\Position;

final class CoUpGameEngine
{
    /**
     * @throws IllegalMoveException
     */
    public static function makeMove(CoUpGameState $state, Position $from, Position $to): CoUpGameState
    {
        if ($state->status === GameStatus::Checkmate || $state->status === GameStatus::Stalemate) {
            throw new IllegalMoveException($from, $to);
        }

        $piece = $state->board->get($from);
        if ($piece === null || $piece->side !== $state->turn) {
            throw new IllegalMoveException($from, $to);
        }

        $move = null;
        foreach (CoUpRules::getLegalMoves($state->board, $from) as $candidate) {
            if ($candidate->to->equals($to)) {
                $move = $candidate;
                break;
            }
        }

        if ($move === null) {
            throw new IllegalMoveException($from, $to);
        }

        $board = $state->board->clone();
        // Any move - including the first one - permanently reveals the piece.
        $board->set($move->to, $move->piece->reveal());
        $board->set($move->from, null);

        $nextTurn = $state->turn->opponent();

        $revealedMove = new CoUpMove($move->from, $move->to, $move->piece->reveal(), $move->captured);

        return new CoUpGameState(
            board: $board,
            turn: $nextTurn,
            moveHistory: [...$state->moveHistory, $revealedMove],
            status: CoUpGameStatusResolver::resolve($board, $nextTurn),
        );
    }
}
