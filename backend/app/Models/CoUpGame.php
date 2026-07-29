<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['board', 'turn', 'move_history', 'status'])]
class CoUpGame extends Model
{
    protected function casts(): array
    {
        return [
            'board' => 'array',
            'move_history' => 'array',
        ];
    }
}
