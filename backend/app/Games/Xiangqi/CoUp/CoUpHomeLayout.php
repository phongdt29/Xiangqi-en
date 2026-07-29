<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;

/**
 * The standard Xiangqi starting layout, keyed by square instead of by side -
 * an unrevealed Cờ Úp piece can only ever still be sitting on its own home
 * square (any move reveals it), so this lookup is always valid wherever an
 * unrevealed piece currently sits.
 */
final class CoUpHomeLayout
{
    private const BACK_RANK = [
        PieceType::Chariot, PieceType::Horse, PieceType::Elephant, PieceType::Advisor,
        PieceType::General, PieceType::Advisor, PieceType::Elephant, PieceType::Horse, PieceType::Chariot,
    ];

    public static function typeAt(Position $pos): ?PieceType
    {
        if ($pos->y === 0 || $pos->y === 9) {
            return self::BACK_RANK[$pos->x] ?? null;
        }

        if (($pos->y === 2 || $pos->y === 7) && in_array($pos->x, [1, 7], true)) {
            return PieceType::Cannon;
        }

        if (($pos->y === 3 || $pos->y === 6) && in_array($pos->x, [0, 2, 4, 6, 8], true)) {
            return PieceType::Soldier;
        }

        return null;
    }

    /** @return Position[] The 15 non-General home squares for one side. */
    public static function nonGeneralSquares(int $backRow, int $cannonRow, int $soldierRow): array
    {
        $squares = [];
        foreach (self::BACK_RANK as $x => $type) {
            if ($type !== PieceType::General) {
                $squares[] = new Position($x, $backRow);
            }
        }
        foreach ([1, 7] as $x) {
            $squares[] = new Position($x, $cannonRow);
        }
        foreach ([0, 2, 4, 6, 8] as $x) {
            $squares[] = new Position($x, $soldierRow);
        }

        return $squares;
    }

    /** @return PieceType[] The 15 non-General piece types, one per non-General home square. */
    public static function nonGeneralTypes(): array
    {
        $types = [];
        foreach (self::BACK_RANK as $type) {
            if ($type !== PieceType::General) {
                $types[] = $type;
            }
        }
        foreach ([1, 7] as $ignored) {
            $types[] = PieceType::Cannon;
        }
        foreach ([0, 2, 4, 6, 8] as $ignored) {
            $types[] = PieceType::Soldier;
        }

        return $types;
    }
}
