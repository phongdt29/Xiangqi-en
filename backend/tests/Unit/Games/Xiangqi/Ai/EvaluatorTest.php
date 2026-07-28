<?php

namespace Tests\Unit\Games\Xiangqi\Ai;

use App\Games\Xiangqi\Ai\Evaluator;
use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\Piece;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class EvaluatorTest extends TestCase
{
    public function test_material_advantage_scores_higher(): void
    {
        $board = new Board;
        $board->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $board->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        $board->set(new Position(0, 0), new Piece(PieceType::Chariot, Side::Red));

        $this->assertGreaterThan(
            Evaluator::evaluate($board, Side::Black),
            Evaluator::evaluate($board, Side::Red),
        );
    }

    public function test_symmetric_position_scores_equal_for_both_sides(): void
    {
        $board = Board::initial();

        $this->assertSame(
            Evaluator::evaluate($board, Side::Red),
            Evaluator::evaluate($board, Side::Black),
        );
    }

    public function test_soldier_crossing_the_river_is_worth_more(): void
    {
        $before = new Board;
        $before->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $before->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        $before->set(new Position(0, 3), new Piece(PieceType::Soldier, Side::Red));

        $after = new Board;
        $after->set(new Position(4, 0), new Piece(PieceType::General, Side::Red));
        $after->set(new Position(4, 9), new Piece(PieceType::General, Side::Black));
        $after->set(new Position(0, 5), new Piece(PieceType::Soldier, Side::Red));

        $this->assertGreaterThan(
            Evaluator::evaluate($before, Side::Red),
            Evaluator::evaluate($after, Side::Red),
        );
    }
}
