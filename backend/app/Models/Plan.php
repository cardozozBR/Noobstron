<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Plan $plan): void {
            $plan->code = strtolower(
                trim(
                    (string) $plan->code
                )
            );

            $plan->name = trim(
                (string) $plan->name
            );

            if ($plan->code === '') {
                throw new InvalidArgumentException(
                    'Plan code is required.'
                );
            }

            if (! preg_match(
                '/^[a-z0-9_-]+$/',
                $plan->code
            )) {
                throw new InvalidArgumentException(
                    'Plan code is invalid.'
                );
            }

            if ($plan->name === '') {
                throw new InvalidArgumentException(
                    'Plan name is required.'
                );
            }
        });
    }

    public function features(): HasMany
    {
        return $this->hasMany(
            PlanFeature::class
        );
    }

    public function prices(): HasMany
    {
        return $this->hasMany(
            PlanPrice::class
        );
    }

    public function usageLimits(): HasMany
    {
        return $this->hasMany(
            PlanUsageLimit::class
        );
    }
}