<?php

namespace Tests\Unit\Games\Xiangqi\Ai;

use App\Games\Xiangqi\Ai\MinimaxAi;
use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\GameState;
use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\Piece;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class MinimaxAiTest extends TestCase
{
    public function test_finds_mate_in_one(): void
    {
        $board = new Board;
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        // These two horses cover the general's (3,9) and (5,9) flight squares
        // only - (4,8) stays open as an escape route until the chariot below
        // closes it by sliding onto column 4, so no other move is mate.
        $board->set(new Position(1, 8), new Piece(PieceType::Horse, Side::Red));
        $board->set(new Position(7, 8), new Piece(PieceType::Horse, Side::Red));
        // Row 1 is otherwise empty, so this chariot isn't attacking anything
        // yet - sliding it onto column 4 is the only mating move.
        $board->set(new Position(7, 1), new Piece(PieceType::Chariot, Side::Red));

        $state = new GameState($board, Side::Red, [], GameStatus::Active);

        $move = MinimaxAi::chooseMove($state, maxDepth: 2, timeLimitSeconds: 3.0);

        $this->assertSame(7, $move->from->x);
        $this->assertSame(1, $move->from->y);
        $this->assertSame(4, $move->to->x);
        $this->assertSame(1, $move->to->y);
    }

    public function test_does_not_hang_a_piece_when_a_safe_move_exists(): void
    {
        $board = new Board;
        $board->set(new Position(3, 0), new Piece(PieceType::General, Side::Red));
        $board->set(new Position(7, 9), new Piece(PieceType::General, Side::Black));
        $board->set(new Position(4, 4), new Piece(PieceType::Horse, Side::Red));
        $board->set(new Position(0, 3), new Piece(PieceType::Soldier, Side::Red));
        // Open column 4: this chariot would capture the Horse for free next
        // turn unless Red moves it (or otherwise deals with the threat) now.
        $board->set(new Position(4, 8), new Piece(PieceType::Chariot, Side::Black));

        $state = new GameState($board, Side::Red, [], GameStatus::Active);

        $move = MinimaxAi::chooseMove($state, maxDepth: 2, timeLimitSeconds: 3.0);

        $this->assertSame(4, $move->from->x);
        $this->assertSame(4, $move->from->y);
    }

    public function test_random_top_n_only_ever_returns_legal_moves(): void
    {
        $state = GameState::initial();

        for ($i = 0; $i < 10; $i++) {
            $move = MinimaxAi::chooseMove($state, maxDepth: 1, timeLimitSeconds: 2.0, randomTopN: 3);
            $this->assertSame($state->board->get($move->from)?->side, Side::Red);
        }
    }
}
