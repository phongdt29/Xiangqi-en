<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\Position;

final readonly class CoUpMove
{
    public function __construct(
        public Position $from,
        public Position $to,
        public CoUpPiece $piece,
        public ?CoUpPiece $captured = null,
    ) {}

    /**
     * A move that already happened is never masked: the mover revealed
     * itself by moving, and a captured piece is off the board - no more
     * secrecy to protect, same as flipping a face-down piece as it's removed
     * in physical play.
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toArray(),
            'to' => $this->to->toArray(),
            'piece' => $this->piece->toArray(false),
            'captured' => $this->captured?->toArray(false),
        ];
    }
}
