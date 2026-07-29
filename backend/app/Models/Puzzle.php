<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'board', 'turn', 'mate_in', 'difficulty'])]
class Puzzle extends Model
{
    protected function casts(): array
    {
        return [
            'board' => 'array',
            'mate_in' => 'integer',
        ];
    }
}
