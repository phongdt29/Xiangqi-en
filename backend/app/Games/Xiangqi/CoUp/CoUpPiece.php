<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Side;

final readonly class CoUpPiece
{
    public function __construct(
        public PieceType $trueType,
        public Side $side,
        public bool $revealed,
    ) {}

    public function reveal(): self
    {
        return new self($this->trueType, $this->side, true);
    }

    /**
     * @return array{side: string, revealed: bool, type?: string}
     */
    public function toArray(bool $mask): array
    {
        if ($mask && ! $this->revealed) {
            return ['side' => $this->side->value, 'revealed' => false];
        }

        return ['type' => $this->trueType->value, 'side' => $this->side->value, 'revealed' => $this->revealed];
    }

    /** Always unmasked - only ever used to hydrate the DB's ground truth. */
    public static function fromArray(array $data): self
    {
        return new self(PieceType::from($data['type']), Side::from($data['side']), (bool) $data['revealed']);
    }
}
