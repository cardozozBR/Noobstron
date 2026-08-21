<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Models\PlanUsageLimit;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\UsageLimitResolution;

class TenantUsageLimitResolver
{
    public function resolve(
        Tenant $tenant,
        UsageMetric $metric
    ): UsageLimitResolution {
        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'status',
                SubscriptionStatus::ACTIVE->value
            )
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return UsageLimitResolution::unavailable();
        }

        $plan = $subscription->plan;

        $usageLimit = PlanUsageLimit::query()
            ->where(
                'plan_id',
                $plan->id
            )
            ->where(
                'metric',
                $metric->value
            )
            ->first();

        if ($usageLimit === null) {
            return UsageLimitResolution::unavailable(
                $plan
            );
        }

        if ($usageLimit->limit_value === null) {
            return UsageLimitResolution::unlimited(
                $plan
            );
        }

        return UsageLimitResolution::limited(
            $plan,
            $usageLimit->limit_value
        );
    }
}