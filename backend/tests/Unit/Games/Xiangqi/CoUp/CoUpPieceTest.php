<?php

namespace Tests\Unit\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\CoUp\CoUpPiece;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Side;
use PHPUnit\Framework\TestCase;

class CoUpPieceTest extends TestCase
{
    public function test_masked_array_never_leaks_the_type_of_an_unrevealed_piece(): void
    {
        $piece = new CoUpPiece(PieceType::General, Side::Red, false);

        $masked = $piece->toArray(mask: true);

        $this->assertArrayNotHasKey('type', $masked);
        $this->assertSame('red', $masked['side']);
        $this->assertFalse($masked['revealed']);
    }

    public function test_masked_array_still_shows_the_type_once_revealed(): void
    {
        $piece = new CoUpPiece(PieceType::Chariot, Side::Black, true);

        $masked = $piece->toArray(mask: true);

        $this->assertSame('chariot', $masked['type']);
    }

    public function test_unmasked_array_always_shows_the_true_type(): void
    {
        $piece = new CoUpPiece(PieceType::Horse, Side::Red, false);

        $unmasked = $piece->toArray(mask: false);

        $this->assertSame('horse', $unmasked['type']);
    }

    public function test_reveal_returns_a_new_revealed_instance_without_mutating_the_original(): void
    {
        $piece = new CoUpPiece(PieceType::Cannon, Side::Black, false);

        $revealed = $piece->reveal();

        $this->assertFalse($piece->revealed);
        $this->assertTrue($revealed->revealed);
        $this->assertSame(PieceType::Cannon, $revealed->trueType);
    }
}
