<?php

namespace Tests\Unit\Games\Xiangqi;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\GameStatusResolver;
use App\Games\Xiangqi\Piece;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Rules;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class GameStatusResolverTest extends TestCase
{
    public function test_detects_checkmate(): void
    {
        $board = new Board;
        // Black general boxed into the back-rank center of its palace.
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        // Column 4 is wide open - direct check.
        $board->set(new Position(4, 0), new Piece(PieceType::Chariot, Side::Red));
        // Covers the (3,9) flight square (and also (4,8)).
        $board->set(new Position(0, 9), new Piece(PieceType::Chariot, Side::Red));
        // Covers the (5,9) flight square (and also (4,8)).
        $board->set(new Position(8, 9), new Piece(PieceType::Chariot, Side::Red));

        $this->assertSame(GameStatus::Checkmate, GameStatusResolver::resolve($board, Side::Black));
    }

    public function test_check_with_an_available_escape_is_not_checkmate(): void
    {
        $board = new Board;
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        $board->set(new Position(4, 0), new Piece(PieceType::Chariot, Side::Red));
        // No pieces cover (3,9)/(5,9)/(4,8), so the general can step aside.

        $this->assertSame(GameStatus::Check, GameStatusResolver::resolve($board, Side::Black));
    }

    public function test_detects_stalemate_which_is_a_loss_not_a_draw(): void
    {
        $board = new Board;
        // Black's only piece: a general with no legal moves, but not currently in check.
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        // These two horses jointly cover (3,9), (4,8) and (5,9) without attacking (4,9) itself.
        $board->set(new Position(2, 7), new Piece(PieceType::Horse, Side::Red));
        $board->set(new Position(6, 7), new Piece(PieceType::Horse, Side::Red));

        $this->assertFalse(Rules::isGeneralInCheck($board, Side::Black));
        $this->assertSame(GameStatus::Stalemate, GameStatusResolver::resolve($board, Side::Black));
    }

    public function test_active_when_moves_are_available(): void
    {
        $board = Board::initial();

        $this->assertSame(GameStatus::Active, GameStatusResolver::resolve($board, Side::Red));
    }
}
