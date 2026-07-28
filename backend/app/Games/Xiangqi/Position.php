<?php

namespace App\Games\Xiangqi;

final readonly class Position
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}

    public function equals(Position $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }

    public function withOffset(int $dx, int $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy);
    }

    /** @return array{x: int, y: int} */
    public function toArray(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }

    public static function fromArray(array $data): self
    {
        return new self((int) $data['x'], (int) $data['y']);
    }
}
