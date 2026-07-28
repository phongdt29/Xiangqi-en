<?php

namespace Tests\Unit\Games\Xiangqi;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\Piece;
use App\Games\Xiangqi\PieceMoves;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class PieceMovesTest extends TestCase
{
    private function assertMovesEqual(array $expected, array $actual): void
    {
        $normalize = fn (Position $p) => "{$p->x},{$p->y}";
        $this->assertEqualsCanonicalizing(
            array_map($normalize, $expected),
            array_map($normalize, $actual),
        );
    }

    public function test_general_moves_within_palace_center(): void
    {
        $board = new Board;
        $pos = new Position(4, 1);
        $board->set($pos, new Piece(PieceType::General, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([
            new Position(5, 1), new Position(3, 1), new Position(4, 2), new Position(4, 0),
        ], $moves);
    }

    public function test_general_confined_to_palace_corner(): void
    {
        $board = new Board;
        $pos = new Position(3, 0);
        $board->set($pos, new Piece(PieceType::General, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([new Position(4, 0), new Position(3, 1)], $moves);
    }

    public function test_advisor_moves_diagonally_within_palace(): void
    {
        $board = new Board;
        $pos = new Position(4, 1);
        $board->set($pos, new Piece(PieceType::Advisor, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([
            new Position(5, 2), new Position(5, 0), new Position(3, 2), new Position(3, 0),
        ], $moves);
    }

    public function test_advisor_confined_to_palace_corner(): void
    {
        $board = new Board;
        $pos = new Position(3, 0);
        $board->set($pos, new Piece(PieceType::Advisor, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([new Position(4, 1)], $moves);
    }

    public function test_elephant_moves_and_river_confinement(): void
    {
        $board = new Board;
        $pos = new Position(2, 0);
        $board->set($pos, new Piece(PieceType::Elephant, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        // Cannot go to (0,-2)/(4,-2) (out of bounds) nor cross the river.
        $this->assertMovesEqual([new Position(0, 2), new Position(4, 2)], $moves);
    }

    public function test_elephant_blocked_by_occupied_eye(): void
    {
        $board = new Board;
        $pos = new Position(2, 0);
        $board->set($pos, new Piece(PieceType::Elephant, Side::Red));
        $board->set(new Position(3, 1), new Piece(PieceType::Soldier, Side::Red)); // eye for (4,2)

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([new Position(0, 2)], $moves);
    }

    public function test_elephant_cannot_cross_river(): void
    {
        $board = new Board;
        $pos = new Position(2, 4);
        $board->set($pos, new Piece(PieceType::Elephant, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        foreach ($moves as $move) {
            $this->assertLessThanOrEqual(4, $move->y);
        }
    }

    public function test_horse_moves_on_open_board(): void
    {
        $board = new Board;
        $pos = new Position(4, 4);
        $board->set($pos, new Piece(PieceType::Horse, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([
            new Position(5, 6), new Position(5, 2), new Position(3, 6), new Position(3, 2),
            new Position(6, 5), new Position(6, 3), new Position(2, 5), new Position(2, 3),
        ], $moves);
    }

    public function test_horse_is_hobbled_by_leg_piece(): void
    {
        $board = new Board;
        $pos = new Position(4, 4);
        $board->set($pos, new Piece(PieceType::Horse, Side::Red));
        $board->set(new Position(4, 5), new Piece(PieceType::Soldier, Side::Red)); // blocks (5,6) and (3,6)

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([
            new Position(5, 2), new Position(3, 2),
            new Position(6, 5), new Position(6, 3), new Position(2, 5), new Position(2, 3),
        ], $moves);
    }

    public function test_chariot_slides_until_blocked_and_can_capture(): void
    {
        $board = new Board;
        $pos = new Position(4, 4);
        $board->set($pos, new Piece(PieceType::Chariot, Side::Red));
        $board->set(new Position(4, 7), new Piece(PieceType::Soldier, Side::Red)); // own piece blocks upward
        $board->set(new Position(7, 4), new Piece(PieceType::Soldier, Side::Black)); // enemy piece capturable

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([
            new Position(4, 5), new Position(4, 6),
            new Position(4, 3), new Position(4, 2), new Position(4, 1), new Position(4, 0),
            new Position(3, 4), new Position(2, 4), new Position(1, 4), new Position(0, 4),
            new Position(5, 4), new Position(6, 4), new Position(7, 4),
        ], $moves);
    }

    public function test_cannon_slides_without_capture_and_jumps_exactly_one_screen_to_capture(): void
    {
        $board = new Board;
        $pos = new Position(4, 4);
        $board->set($pos, new Piece(PieceType::Cannon, Side::Red));
        $board->set(new Position(4, 6), new Piece(PieceType::Soldier, Side::Red)); // screen
        $board->set(new Position(4, 8), new Piece(PieceType::Soldier, Side::Black)); // capturable beyond screen

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        // Non-capturing slide only reaches (4,5); the screen itself is not a valid landing
        // square, and the jump capture lands exactly on the enemy piece beyond the screen.
        $this->assertContainsEquals(new Position(4, 5), $moves);
        $this->assertContainsEquals(new Position(4, 8), $moves);
        $this->assertNotContainsEquals(new Position(4, 6), $moves);
        $this->assertNotContainsEquals(new Position(4, 7), $moves);
    }

    public function test_cannon_cannot_capture_own_piece_beyond_screen(): void
    {
        $board = new Board;
        $pos = new Position(4, 4);
        $board->set($pos, new Piece(PieceType::Cannon, Side::Red));
        $board->set(new Position(4, 6), new Piece(PieceType::Soldier, Side::Red));
        $board->set(new Position(4, 8), new Piece(PieceType::Soldier, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertNotContainsEquals(new Position(4, 8), $moves);
    }

    public function test_soldier_before_crossing_river_moves_forward_only(): void
    {
        $board = new Board;
        $pos = new Position(4, 3);
        $board->set($pos, new Piece(PieceType::Soldier, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([new Position(4, 4)], $moves);
    }

    public function test_soldier_after_crossing_river_moves_forward_left_right(): void
    {
        $board = new Board;
        $pos = new Position(4, 6);
        $board->set($pos, new Piece(PieceType::Soldier, Side::Red));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([
            new Position(4, 7), new Position(3, 6), new Position(5, 6),
        ], $moves);
    }

    public function test_black_soldier_forward_direction_is_mirrored(): void
    {
        $board = new Board;
        $pos = new Position(4, 6);
        $board->set($pos, new Piece(PieceType::Soldier, Side::Black));

        $moves = PieceMoves::pseudoLegalMoves($board, $pos);

        $this->assertMovesEqual([new Position(4, 5)], $moves);
    }
}
