<?php

namespace App\Games\Xiangqi;

final class GameStatusResolver
{
    public static function resolve(Board $board, Side $sideToMove): GameStatus
    {
        $inCheck = Rules::isGeneralInCheck($board, $sideToMove);
        $hasMoves = Rules::getAllLegalMoves($board, $sideToMove) !== [];

        if ($inCheck) {
            return $hasMoves ? GameStatus::Check : GameStatus::Checkmate;
        }

        // Unlike international chess, a side with no legal moves that is NOT
        // in check still loses in Xiangqi - it is not a draw.
        return $hasMoves ? GameStatus::Active : GameStatus::Stalemate;
    }
}
