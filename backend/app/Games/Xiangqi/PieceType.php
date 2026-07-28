<?php

namespace App\Games\Xiangqi;

enum PieceType: string
{
    case General = 'general';
    case Advisor = 'advisor';
    case Elephant = 'elephant';
    case Horse = 'horse';
    case Chariot = 'chariot';
    case Cannon = 'cannon';
    case Soldier = 'soldier';
}
