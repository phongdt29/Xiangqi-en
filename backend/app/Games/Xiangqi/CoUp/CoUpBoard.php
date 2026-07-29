<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;

final class CoUpBoard
{
    public const WIDTH = 9;

    public const HEIGHT = 10;

    /** @var array<int, array<int, ?CoUpPiece>> */
    private array $grid;

    /** @param array<int, array<int, ?CoUpPiece>>|null $grid */
    public function __construct(?array $grid = null)
    {
        $this->grid = $grid ?? self::emptyGrid();
    }

    /** @return array<int, array<int, ?CoUpPiece>> */
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

        $board->set(new Position(4, 0), new CoUpPiece(PieceType::General, Side::Red, true));
        $board->set(new Position(4, 9), new CoUpPiece(PieceType::General, Side::Black, true));

        foreach ([
            [Side::Red, 0, 2, 3],
            [Side::Black, 9, 7, 6],
        ] as [$side, $backRow, $cannonRow, $soldierRow]) {
            $squares = CoUpHomeLayout::nonGeneralSquares($backRow, $cannonRow, $soldierRow);
            $types = CoUpHomeLayout::nonGeneralTypes();
            shuffle($types);

            foreach ($squares as $i => $pos) {
                $board->set($pos, new CoUpPiece($types[$i], $side, false));
            }
        }

        return $board;
    }

    public static function inBounds(Position $pos): bool
    {
        return $pos->x >= 0 && $pos->x < self::WIDTH && $pos->y >= 0 && $pos->y < self::HEIGHT;
    }

    public function get(Position $pos): ?CoUpPiece
    {
        return $this->grid[$pos->y][$pos->x] ?? null;
    }

    public function set(Position $pos, ?CoUpPiece $piece): void
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
                if ($piece !== null && $piece->trueType === PieceType::General && $piece->side === $side) {
                    return new Position($x, $y);
                }
            }
        }

        return null;
    }

    /** @return array<int, array{0: Position, 1: CoUpPiece}> */
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

    /** @return array<int, array<int, ?array>> */
    public function toArray(bool $mask): array
    {
        $out = [];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $row = [];
            for ($x = 0; $x < self::WIDTH; $x++) {
                $row[] = $this->grid[$y][$x]?->toArray($mask);
            }
            $out[] = $row;
        }

        return $out;
    }

    /** @param array<int, array<int, ?array>> $rows Always unmasked - only ever used to hydrate the DB's ground truth. */
    public static function fromArray(array $rows): self
    {
        $board = new self;
        foreach ($rows as $y => $row) {
            foreach ($row as $x => $cell) {
                $board->set(new Position($x, $y), $cell === null ? null : CoUpPiece::fromArray($cell));
            }
        }

        return $board;
    }
}
