<?php

namespace App\Models;

use App\Support\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class PlanPrice extends Model
{
    protected $fillable = [
        'stripe_price_id',
        'plan_id',
        'currency',
        'amount_minor',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PlanPrice $price): void {
            $price->currency = Currency::normalize(
                (string) $price->currency
            );

            if ($price->amount_minor < 0) {
                throw new InvalidArgumentException(
                    'Plan price amount must be non-negative.'
                );
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            Plan::class
        );
    }
}