<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'package_key', 'points', 'amount_usd', 'paypal_order_id', 'status'])]
class PointTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'amount_usd' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
