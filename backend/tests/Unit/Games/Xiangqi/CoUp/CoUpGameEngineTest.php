<?php

namespace Tests\Unit\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\CoUp\CoUpBoard;
use App\Games\Xiangqi\CoUp\CoUpGameEngine;
use App\Games\Xiangqi\CoUp\CoUpGameState;
use App\Games\Xiangqi\CoUp\CoUpPiece;
use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\IllegalMoveException;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class CoUpGameEngineTest extends TestCase
{
    public function test_moving_a_face_down_piece_reveals_its_true_type(): void
    {
        $board = new CoUpBoard;
        $board->set(new Position(4, 0), new CoUpPiece(PieceType::General, Side::Red, true));
        $board->set(new Position(4, 9), new CoUpPiece(PieceType::General, Side::Black, true));
        // Face-down on the advisor square, but its true type is Elephant -
        // proves the *pattern used for the move* (advisor-shaped) is
        // independent from the *type that ends up revealed* (elephant).
        $board->set(new Position(3, 0), new CoUpPiece(PieceType::Elephant, Side::Red, false));

        $state = new CoUpGameState($board, Side::Red, [], GameStatus::Active);
        $next = CoUpGameEngine::makeMove($state, new Position(3, 0), new Position(4, 1));

        $piece = $next->board->get(new Position(4, 1));
        $this->assertNotNull($piece);
        $this->assertTrue($piece->revealed);
        $this->assertSame(PieceType::Elephant, $piece->trueType);
        $this->assertNull($next->board->get(new Position(3, 0)));
        $this->assertSame(Side::Black, $next->turn);
    }

    public function test_move_history_entry_is_never_masked(): void
    {
        $board = new CoUpBoard;
        $board->set(new Position(4, 0), new CoUpPiece(PieceType::General, Side::Red, true));
        $board->set(new Position(4, 9), new CoUpPiece(PieceType::General, Side::Black, true));
        $board->set(new Position(3, 0), new CoUpPiece(PieceType::Elephant, Side::Red, false));

        $state = new CoUpGameState($board, Side::Red, [], GameStatus::Active);
        $next = CoUpGameEngine::makeMove($state, new Position(3, 0), new Position(4, 1));

        $entry = $next->moveHistory[0]->toArray();
        $this->assertSame('elephant', $entry['piece']['type']);
    }

    public function test_rejects_a_move_that_is_not_legal(): void
    {
        $board = CoUpBoard::initial();
        $state = new CoUpGameState($board, Side::Red, [], GameStatus::Active);

        $this->expectException(IllegalMoveException::class);
        CoUpGameEngine::makeMove($state, new Position(4, 0), new Position(4, 5));
    }

    public function test_rejects_moving_out_of_turn(): void
    {
        $board = CoUpBoard::initial();
        $state = new CoUpGameState($board, Side::Red, [], GameStatus::Active);

        // (4,9) is Black's general - it's Red's turn.
        $this->expectException(IllegalMoveException::class);
        CoUpGameEngine::makeMove($state, new Position(4, 9), new Position(4, 8));
    }
}
