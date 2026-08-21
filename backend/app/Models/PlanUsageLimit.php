<?php

namespace App\Models;

use App\Enums\UsageMetric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class PlanUsageLimit extends Model
{
    protected $fillable = [
        'plan_id',
        'metric',
        'limit_value',
    ];

    protected function casts(): array
    {
        return [
            'metric' => UsageMetric::class,
            'limit_value' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(
            function (PlanUsageLimit $usageLimit): void {
                if (
                    $usageLimit->limit_value !== null
                    && $usageLimit->limit_value < 0
                ) {
                    throw new InvalidArgumentException(
                        'Plan usage limit must be null or non-negative.'
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