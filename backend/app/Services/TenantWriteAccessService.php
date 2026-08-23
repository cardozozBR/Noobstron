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
        $hasPaidSubscriptionHistory = Subscription::query()
            ->withoutGlobalScopes()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->whereNotNull('paid_at')
            ->exists();

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

        /*
         * Once the tenant has paid for a subscription, historical
         * trial dates must never restore write access after that
         * subscription is cancelled or expires.
         */
        if ($hasPaidSubscriptionHistory) {
            return false;
        }

        if (
            $this->trials->status($tenant)
            === TrialService::STATUS_ACTIVE
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
