<?php

namespace App\Games\Xiangqi;

final class Board
{
    public const WIDTH = 9;

    public const HEIGHT = 10;

    /** @var array<int, array<int, ?Piece>> */
    private array $grid;

    /** @param array<int, array<int, ?Piece>>|null $grid */
    public function __construct(?array $grid = null)
    {
        $this->grid = $grid ?? self::emptyGrid();
    }

    /** @return array<int, array<int, ?Piece>> */
    private static function emptyGrid(): array
    {
        $grid = [];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $grid[$y] = array_fill(0, self::WIDTH, null);
        }

        return $grid;
    }

    public static function initial(): self
    {
        $board = new self;

        $backRank = [
            PieceType::Chariot, PieceType::Horse, PieceType::Elephant, PieceType::Advisor,
            PieceType::General, PieceType::Advisor, PieceType::Elephant, PieceType::Horse, PieceType::Chariot,
        ];

        foreach ($backRank as $x => $type) {
            $board->set(new Position($x, 0), new Piece($type, Side::Red));
            $board->set(new Position($x, 9), new Piece($type, Side::Black));
        }

        foreach ([1, 7] as $x) {
            $board->set(new Position($x, 2), new Piece(PieceType::Cannon, Side::Red));
            $board->set(new Position($x, 7), new Piece(PieceType::Cannon, Side::Black));
        }

        foreach ([0, 2, 4, 6, 8] as $x) {
            $board->set(new Position($x, 3), new Piece(PieceType::Soldier, Side::Red));
            $board->set(new Position($x, 6), new Piece(PieceType::Soldier, Side::Black));
        }

        return $board;
    }

    public static function inBounds(Position $pos): bool
    {
        return $pos->x >= 0 && $pos->x < self::WIDTH && $pos->y >= 0 && $pos->y < self::HEIGHT;
    }

    public function get(Position $pos): ?Piece
    {
        return $this->grid[$pos->y][$pos->x] ?? null;
    }

    public function set(Position $pos, ?Piece $piece): void
    {
        $this->grid[$pos->y][$pos->x] = $piece;
    }

    public function clone(): self
    {
        return new self($this->grid);
    }

    public function findGeneral(Side $side): ?Position
    {
        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                $piece = $this->grid[$y][$x];
                if ($piece !== null && $piece->type === PieceType::General && $piece->side === $side) {
                    return new Position($x, $y);
                }
            }
        }

        return null;
    }

    /** @return array<int, array{0: Position, 1: Piece}> */
    public function piecesOf(Side $side): array
    {
        $result = [];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                $piece = $this->grid[$y][$x];
                if ($piece !== null && $piece->side === $side) {
                    $result[] = [new Position($x, $y), $piece];
                }
            }
        }

        return $result;
    }

    /** @return array<int, array<int, ?array{type: string, side: string}>> */
    public function toArray(): array
    {
        $out = [];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $row = [];
            for ($x = 0; $x < self::WIDTH; $x++) {
                $row[] = $this->grid[$y][$x]?->toArray();
            }
            $out[] = $row;
        }

        return $out;
    }

    /** @param array<int, array<int, ?array{type: string, side: string}>> $rows */
    public static function fromArray(array $rows): self
    {
        $board = new self;
        foreach ($rows as $y => $row) {
            foreach ($row as $x => $cell) {
                $board->set(new Position($x, $y), $cell === null ? null : Piece::fromArray($cell));
            }
        }

        return $board;
    }
}
