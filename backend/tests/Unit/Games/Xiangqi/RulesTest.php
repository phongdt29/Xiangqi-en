<?php

namespace Tests\Unit\Games\Xiangqi;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\Piece;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Rules;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    public function test_detects_check_from_an_open_chariot_line(): void
    {
        $board = new Board;
        $board->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $board->set(new Position(4, 5), new Piece(PieceType::Chariot, Side::Black));

        $this->assertTrue(Rules::isGeneralInCheck($board, Side::Red));
    }

    public function test_not_in_check_when_line_is_blocked(): void
    {
        $board = new Board;
        $board->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $board->set(new Position(4, 3), new Piece(PieceType::Soldier, Side::Red));
        $board->set(new Position(4, 5), new Piece(PieceType::Chariot, Side::Black));

        $this->assertFalse(Rules::isGeneralInCheck($board, Side::Red));
    }

    public function test_move_that_exposes_own_general_is_illegal(): void
    {
        $board = new Board;
        $board->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $blocker = new Position(4, 1);
        $board->set($blocker, new Piece(PieceType::Chariot, Side::Red));
        $board->set(new Position(4, 9), new Piece(PieceType::Chariot, Side::Black));

        $moves = Rules::getLegalMoves($board, $blocker);
        $destinations = array_map(fn ($m) => "{$m->to->x},{$m->to->y}", $moves);

        // Sliding sideways off column 4 would expose the general - illegal.
        $this->assertNotContains('3,1', $destinations);
        $this->assertNotContains('5,1', $destinations);
        // Staying on column 4 keeps the general protected - legal.
        $this->assertContains('4,2', $destinations);
    }

    public function test_flying_general_violation_when_columns_align_with_no_blocker(): void
    {
        $board = new Board;
        $board->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));

        $this->assertTrue(Rules::violatesFlyingGeneralRule($board));
    }

    public function test_no_flying_general_violation_when_blocked(): void
    {
        $board = new Board;
        $board->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $board->set(new Position(4, 5), new Piece(PieceType::Soldier, Side::Red));
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));

        $this->assertFalse(Rules::violatesFlyingGeneralRule($board));
    }

    public function test_move_that_would_expose_generals_to_each_other_is_illegal(): void
    {
        $board = new Board;
        $board->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $blocker = new Position(4, 4);
        $board->set($blocker, new Piece(PieceType::Chariot, Side::Red));
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));

        $moves = Rules::getLegalMoves($board, $blocker);
        $destinations = array_map(fn ($m) => "{$m->to->x},{$m->to->y}", $moves);

        // Sliding off column 4 would put both generals face to face - illegal.
        $this->assertNotContains('3,4', $destinations);
        $this->assertNotContains('0,4', $destinations);
        // Sliding along column 4 keeps a blocker between the generals - legal.
        $this->assertContains('4,3', $destinations);
    }
}
