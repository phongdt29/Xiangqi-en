<?php

namespace Tests\Unit\Games\Xiangqi;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\GameEngine;
use App\Games\Xiangqi\GameState;
use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\IllegalMoveException;
use App\Games\Xiangqi\Piece;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class GameEngineTest extends TestCase
{
    public function test_initial_state(): void
    {
        $state = GameState::initial();

        $this->assertSame(Side::Red, $state->turn);
        $this->assertSame(GameStatus::Active, $state->status);
        $this->assertSame([], $state->moveHistory);
        $this->assertEquals(
            new Piece(PieceType::General, Side::Red),
            $state->board->get(new Position(4, 0)),
        );
        $this->assertEquals(
            new Piece(PieceType::General, Side::Black),
            $state->board->get(new Position(4, 9)),
        );
    }

    public function test_valid_move_updates_board_and_switches_turn(): void
    {
        $state = GameState::initial();

        $next = GameEngine::makeMove($state, new Position(0, 3), new Position(0, 4));

        $this->assertSame(Side::Black, $next->turn);
        $this->assertNull($next->board->get(new Position(0, 3)));
        $this->assertEquals(
            new Piece(PieceType::Soldier, Side::Red),
            $next->board->get(new Position(0, 4)),
        );
        $this->assertCount(1, $next->moveHistory);
    }

    public function test_moving_opponents_piece_is_illegal(): void
    {
        $state = GameState::initial();

        $this->expectException(IllegalMoveException::class);
        GameEngine::makeMove($state, new Position(0, 6), new Position(0, 5));
    }

    public function test_move_that_does_not_match_piece_pattern_is_illegal(): void
    {
        $state = GameState::initial();

        $this->expectException(IllegalMoveException::class);
        // Chariot cannot jump diagonally over its own row.
        GameEngine::makeMove($state, new Position(0, 0), new Position(2, 2));
    }

    public function test_capture_records_captured_piece(): void
    {
        $board = new Board;
        $board->set(new Position(4, 4), new Piece(PieceType::Chariot, Side::Red));
        $board->set(new Position(4, 6), new Piece(PieceType::Soldier, Side::Black));
        $state = new GameState($board, Side::Red, [], GameStatus::Active);

        $next = GameEngine::makeMove($state, new Position(4, 4), new Position(4, 6));

        $this->assertEquals(
            new Piece(PieceType::Chariot, Side::Red),
            $next->board->get(new Position(4, 6)),
        );
        $lastMove = $next->moveHistory[array_key_last($next->moveHistory)];
        $this->assertEquals(new Piece(PieceType::Soldier, Side::Black), $lastMove->captured);
    }

    public function test_move_recomputes_status_to_check(): void
    {
        $board = new Board;
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        $board->set(new Position(3, 1), new Piece(PieceType::Chariot, Side::Red));
        $state = new GameState($board, Side::Red, [], GameStatus::Active);

        $next = GameEngine::makeMove($state, new Position(3, 1), new Position(4, 1));

        $this->assertSame(Side::Black, $next->turn);
        $this->assertSame(GameStatus::Check, $next->status);
    }

    public function test_no_moves_are_legal_once_checkmated(): void
    {
        $board = new Board;
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        $board->set(new Position(3, 8), new Piece(PieceType::Advisor, Side::Black));
        $state = new GameState($board, Side::Black, [], GameStatus::Checkmate);

        $this->expectException(IllegalMoveException::class);
        GameEngine::makeMove($state, new Position(3, 8), new Position(4, 7));
    }
}
