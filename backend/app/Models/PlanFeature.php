<?php

namespace App\Models;

use App\Enums\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class PlanFeature extends Model
{
    protected $fillable = [
        'plan_id',
        'feature',
        'enabled',
        'limit_value',
    ];

    protected function casts(): array
    {
        return [
            'feature' => Feature::class,
            'enabled' => 'boolean',
            'limit_value' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(
            function (PlanFeature $planFeature): void {
                if (
                    $planFeature->limit_value !== null
                    && $planFeature->limit_value < 0
                ) {
                    throw new InvalidArgumentException(
                        'Plan feature limit must be null or non-negative.'
                    );
                }
            }
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            Plan::class
        );
    }
}