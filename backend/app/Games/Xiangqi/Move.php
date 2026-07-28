<?php

namespace App\Games\Xiangqi;

final readonly class Move
{
    public function __construct(
        public Position $from,
        public Position $to,
        public Piece $piece,
        public ?Piece $captured = null,
    ) {}

    public function toArray(): array
    {
        return [
            'from' => $this->from->toArray(),
            'to' => $this->to->toArray(),
            'piece' => $this->piece->toArray(),
            'captured' => $this->captured?->toArray(),
        ];
    }
}
