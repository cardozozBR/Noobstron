<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\Tenant;

class TenantWriteAccessService
{
    public function __construct(
        private readonly TrialService $trials,
        private readonly SubscriptionBillingService $billing,
    ) {
    }

    public function allowed(Tenant $tenant): bool
    {
        if (
            $this->trials->status($tenant)
            === TrialService::STATUS_ACTIVE
        ) {
            return true;
        }

        $activeSubscription = Subscription::query()
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

        if (
            $activeSubscription !== null
            && $this->billing->isPaid(
                $activeSubscription
            )
        ) {
            return true;
        }

        $hasSubscriptionHistory = Subscription::query()
            ->withoutGlobalScopes()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->exists();

        $hasTrialHistory =
            $tenant->trial_started_at !== null
            || $tenant->trial_ends_at !== null;

        /*
         * Backward compatibility for tenants created before
         * commercial subscriptions and trials existed.
         */
        return ! $hasSubscriptionHistory
            && ! $hasTrialHistory;
    }
}
