<?php

namespace App\Games\Xiangqi\CoUp;

use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\Side;

final class CoUpGameStatusResolver
{
    public static function resolve(CoUpBoard $board, Side $sideToMove): GameStatus
    {
        $inCheck = CoUpRules::isGeneralInCheck($board, $sideToMove);
        $hasMoves = CoUpRules::getAllLegalMoves($board, $sideToMove) !== [];

        if ($inCheck) {
            return $hasMoves ? GameStatus::Check : GameStatus::Checkmate;
        }

        // Same as standard Xiangqi: a side with no legal moves that is NOT in
        // check still loses - it is not a draw.
        return $hasMoves ? GameStatus::Active : GameStatus::Stalemate;
    }
}
