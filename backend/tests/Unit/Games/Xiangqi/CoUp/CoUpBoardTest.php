<?php

namespace Tests\Unit\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\CoUp\CoUpBoard;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class CoUpBoardTest extends TestCase
{
    public function test_both_generals_start_revealed_and_in_place(): void
    {
        $board = CoUpBoard::initial();

        $red = $board->get(new Position(4, 0));
        $black = $board->get(new Position(4, 9));

        $this->assertNotNull($red);
        $this->assertSame(PieceType::General, $red->trueType);
        $this->assertTrue($red->revealed);

        $this->assertNotNull($black);
        $this->assertSame(PieceType::General, $black->trueType);
        $this->assertTrue($black->revealed);
    }

    public function test_every_other_piece_starts_unrevealed(): void
    {
        $board = CoUpBoard::initial();
        $count = 0;

        foreach ([Side::Red, Side::Black] as $side) {
            foreach ($board->piecesOf($side) as [$pos, $piece]) {
                if ($piece->trueType === PieceType::General) {
                    continue;
                }
                $this->assertFalse($piece->revealed, "Piece at {$pos->x},{$pos->y} should start unrevealed");
                $count++;
            }
        }

        $this->assertSame(30, $count);
    }

    public function test_shuffle_keeps_the_correct_piece_composition_per_side(): void
    {
        $board = CoUpBoard::initial();

        foreach ([Side::Red, Side::Black] as $side) {
            $counts = [];
            foreach ($board->piecesOf($side) as [, $piece]) {
                $counts[$piece->trueType->value] = ($counts[$piece->trueType->value] ?? 0) + 1;
            }
            ksort($counts);

            $this->assertSame([
                'advisor' => 2,
                'cannon' => 2,
                'chariot' => 2,
                'elephant' => 2,
                'general' => 1,
                'horse' => 2,
                'soldier' => 5,
            ], $counts);
        }
    }

    public function test_pieces_are_only_placed_on_that_sides_own_squares(): void
    {
        $board = CoUpBoard::initial();

        foreach ($board->piecesOf(Side::Red) as [$pos]) {
            $this->assertLessThanOrEqual(3, $pos->y);
        }
        foreach ($board->piecesOf(Side::Black) as [$pos]) {
            $this->assertGreaterThanOrEqual(6, $pos->y);
        }
    }
}
