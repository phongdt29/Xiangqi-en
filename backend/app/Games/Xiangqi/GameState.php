<?php

namespace App\Games\Xiangqi;

final readonly class GameState
{
    /** @param Move[] $moveHistory */
    public function __construct(
        public Board $board,
        public Side $turn,
        public array $moveHistory,
        public GameStatus $status,
    ) {}

    public static function initial(): self
    {
        return new self(Board::initial(), Side::Red, [], GameStatus::Active);
    }

    public function toArray(): array
    {
        return [
            'board' => $this->board->toArray(),
            'turn' => $this->turn->value,
            'moveHistory' => array_map(fn (Move $m) => $m->toArray(), $this->moveHistory),
            'status' => $this->status->value,
        ];
    }

    /** @param array<int, array> $moveHistory */
    public static function fromArray(array $board, string $turn, array $moveHistory, string $status): self
    {
        return new self(
            Board::fromArray($board),
            Side::from($turn),
            array_map(fn (array $m) => new Move(
                Position::fromArray($m['from']),
                Position::fromArray($m['to']),
                Piece::fromArray($m['piece']),
                isset($m['captured']) && $m['captured'] !== null ? Piece::fromArray($m['captured']) : null,
            ), $moveHistory),
            GameStatus::from($status),
        );
    }
}
