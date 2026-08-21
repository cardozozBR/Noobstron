<?php

namespace App\Services;

use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\Tenant;
use InvalidArgumentException;

class TenantUsageGuard
{
    public function __construct(
        private readonly TenantUsageAllowanceService $allowances,
    ) {
    }

    public function assertCanConsume(
        Tenant $tenant,
        UsageMetric $metric,
        int $amount = 1,
    ): void {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                'Requested usage cannot be negative.'
            );
        }

        if ($amount === 0) {
            return;
        }

        $allowance = $this->allowances->resolve(
            $tenant,
            $metric
        );

        if (! $allowance->available) {
            throw UsageBlockedException::unavailable(
                metric: $metric,
                used: $allowance->used,
                requested: $amount,
                plan: $allowance->plan,
            );
        }

        if ($allowance->unlimited) {
            return;
        }

        $projected =
            $allowance->used + $amount;

        if ($projected > $allowance->limit) {
            throw UsageBlockedException::exceeded(
                metric: $metric,
                used: $allowance->used,
                requested: $amount,
                limit: $allowance->limit,
                remaining: $allowance->remaining,
                plan: $allowance->plan,
            );
        }
    }
}