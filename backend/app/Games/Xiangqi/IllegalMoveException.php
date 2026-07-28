<?php

namespace App\Games\Xiangqi;

use RuntimeException;

final class IllegalMoveException extends RuntimeException
{
    public function __construct(Position $from, Position $to)
    {
        parent::__construct(sprintf(
            'Illegal move from (%d,%d) to (%d,%d).',
            $from->x,
            $from->y,
            $to->x,
            $to->y,
        ));
    }
}
