<?php

namespace Tests\Unit\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\CoUp\CoUpBoard;
use App\Games\Xiangqi\CoUp\CoUpPiece;
use App\Games\Xiangqi\CoUp\CoUpPieceMoves;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class CoUpPieceMovesTest extends TestCase
{
    private function targets(array $moves): array
    {
        return array_map(fn (Position $p) => [$p->x, $p->y], $moves);
    }

    public function test_unrevealed_piece_on_advisor_square_moves_like_an_advisor_regardless_of_true_type(): void
    {
        $board = new CoUpBoard;
        // True type is Chariot, but it's face-down on Red's advisor square -
        // if the true type leaked through it would have many more moves.
        $board->set(new Position(3, 0), new CoUpPiece(PieceType::Chariot, Side::Red, false));

        $moves = CoUpPieceMoves::pseudoLegalMoves($board, new Position(3, 0));

        $this->assertSame([[4, 1]], $this->targets($moves));
    }

    public function test_revealed_advisor_is_no_longer_confined_to_the_palace(): void
    {
        $board = new CoUpBoard;
        $board->set(new Position(4, 4), new CoUpPiece(PieceType::Advisor, Side::Red, true));

        $moves = CoUpPieceMoves::pseudoLegalMoves($board, new Position(4, 4));

        $this->assertCount(4, $moves);
        $this->assertContains([5, 5], $this->targets($moves));
    }

    public function test_revealed_elephant_can_cross_the_river(): void
    {
        $board = new CoUpBoard;
        $board->set(new Position(4, 3), new CoUpPiece(PieceType::Elephant, Side::Red, true));

        $moves = CoUpPieceMoves::pseudoLegalMoves($board, new Position(4, 3));

        $this->assertContains([6, 5], $this->targets($moves));
    }

    public function test_revealed_elephant_is_still_blocked_by_its_eye(): void
    {
        $board = new CoUpBoard;
        $board->set(new Position(4, 3), new CoUpPiece(PieceType::Elephant, Side::Red, true));
        // Blocks the (4,3)->(6,5) diagonal's eye at (5,4) - the area-restriction
        // unlock must not also lift this unrelated movement-pattern rule.
        $board->set(new Position(5, 4), new CoUpPiece(PieceType::Soldier, Side::Red, true));

        $moves = CoUpPieceMoves::pseudoLegalMoves($board, new Position(4, 3));

        $this->assertNotContains([6, 5], $this->targets($moves));
    }

    public function test_general_is_always_restricted_to_the_palace(): void
    {
        $board = new CoUpBoard;
        $board->set(new Position(4, 0), new CoUpPiece(PieceType::General, Side::Red, true));

        $moves = CoUpPieceMoves::pseudoLegalMoves($board, new Position(4, 0));

        foreach ($this->targets($moves) as [$x, $y]) {
            $this->assertTrue($x >= 3 && $x <= 5 && $y <= 2);
        }
    }
}
