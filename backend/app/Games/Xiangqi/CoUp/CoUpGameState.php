<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;

final readonly class CoUpGameState
{
    /** @param CoUpMove[] $moveHistory */
    public function __construct(
        public CoUpBoard $board,
        public Side $turn,
        public array $moveHistory,
        public GameStatus $status,
    ) {}

    public static function initial(): self
    {
        return new self(CoUpBoard::initial(), Side::Red, [], GameStatus::Active);
    }

    public function toArray(bool $mask): array
    {
        return [
            'board' => $this->board->toArray($mask),
            'turn' => $this->turn->value,
            'moveHistory' => array_map(fn (CoUpMove $m) => $m->toArray(), $this->moveHistory),
            'status' => $this->status->value,
        ];
    }

    /**
     * Always unmasked - only ever used to hydrate the DB's ground truth, not
     * to accept client input (the client never sends board state for Cờ Úp).
     */
    public static function fromArray(array $board, string $turn, array $moveHistory, string $status): self
    {
        return new self(
            CoUpBoard::fromArray($board),
            Side::from($turn),
            array_map(fn (array $m) => new CoUpMove(
                Position::fromArray($m['from']),
                Position::fromArray($m['to']),
                CoUpPiece::fromArray($m['piece']),
                isset($m['captured']) && $m['captured'] !== null ? CoUpPiece::fromArray($m['captured']) : null,
            ), $moveHistory),
            GameStatus::from($status),
        );
    }
}
