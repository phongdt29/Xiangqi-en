<?php

namespace App\Games\Xiangqi;

final readonly class Piece
{
    public function __construct(
        public PieceType $type,
        public Side $side,
    ) {}

    /** @return array{type: string, side: string} */
    public function toArray(): array
    {
        return ['type' => $this->type->value, 'side' => $this->side->value];
    }

    public static function fromArray(array $data): self
    {
        return new self(PieceType::from($data['type']), Side::from($data['side']));
    }
}
