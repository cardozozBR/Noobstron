<?php

namespace App\Services;

use App\Enums\UsageMetric;
use App\Models\Tenant;
use App\Support\UsageAllowance;

class TenantUsageAllowanceService
{
    public function __construct(
        private readonly TenantUsageService $usage,
        private readonly TenantUsageLimitResolver $limits,
    ) {
    }

    public function resolve(
        Tenant $tenant,
        UsageMetric $metric
    ): UsageAllowance {
        $used = $this->usage->value(
            $tenant,
            $metric
        );

        $limit = $this->limits->resolve(
            $tenant,
            $metric
        );

        if (! $limit->available) {
            return UsageAllowance::unavailable(
                used: $used,
                plan: $limit->plan,
            );
        }

        if ($limit->unlimited) {
            return UsageAllowance::unlimited(
                used: $used,
                plan: $limit->plan,
            );
        }

        return UsageAllowance::limited(
            used: $used,
            limit: $limit->limit,
            plan: $limit->plan,
        );
    }
}