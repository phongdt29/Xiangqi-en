<?php

namespace App\Games\Xiangqi;

enum Side: string
{
    case Red = 'red';
    case Black = 'black';

    public function opponent(): self
    {
        return $this === self::Red ? self::Black : self::Red;
    }
}
