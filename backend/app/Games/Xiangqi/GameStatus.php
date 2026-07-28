<?php

namespace App\Games\Xiangqi;

enum GameStatus: string
{
    case Active = 'active';
    case Check = 'check';
    case Checkmate = 'checkmate';
    case Stalemate = 'stalemate';
}
