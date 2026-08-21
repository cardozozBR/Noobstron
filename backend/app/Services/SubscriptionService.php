<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use RuntimeException;

class SubscriptionService
{
    public function __construct(
        private readonly PlanCapabilityProfile $planCapabilityProfile,
        private readonly TenantCapabilityManager $tenantCapabilityManager,
    ) {
    }

    public function create(
        Tenant $tenant,
        Plan $plan,
        SubscriptionPeriod $period,
    ): Subscription {
        $hasCurrentSubscription =
    Subscription::withoutGlobalScopes()
        ->where(
            'tenant_id',
            $tenant->id
        )
        ->whereIn(
            'status',
            [
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::SUSPENDED->value,
            ]
        )
        ->exists();

if ($hasCurrentSubscription) {
    throw new RuntimeException(
        'Tenant already has a current subscription.'
    );
}
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_start' => $period->startsAt,
            'current_period_end' => $period->endsAt,
        ]);

        $profile = $this->planCapabilityProfile
            ->definitions($plan);

        $this->tenantCapabilityManager
            ->applyProfile(
                $tenant,
                $profile
            );

        return $subscription->refresh();
    }

    public function cancel(
        Subscription $subscription
    ): Subscription {
        $this->assertMutable($subscription);

        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
        ]);

        return $subscription->refresh();
    }

    public function suspend(
        Subscription $subscription
    ): Subscription {
        $this->assertMutable($subscription);

        if (
            $subscription->status
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can be suspended.'
            );
        }

        $subscription->update([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);

        return $subscription->refresh();
    }

    public function resume(
        Subscription $subscription
    ): Subscription {
        if (
            $subscription->status
            !== SubscriptionStatus::SUSPENDED
        ) {
            throw new RuntimeException(
                'Only suspended subscriptions can be resumed.'
            );
        }

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        return $subscription->refresh();
    }

    public function changePlan(
        Subscription $subscription,
        Plan $plan,
    ): Subscription {
        if (
            $subscription->status
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can change plan.'
            );
        }

        if ($subscription->plan_id === $plan->id) {
            return $subscription->refresh();
        }

        $subscription->update([
            'plan_id' => $plan->id,
        ]);

        $profile = $this->planCapabilityProfile
            ->definitions($plan);

        $this->tenantCapabilityManager
            ->applyProfile(
                $subscription->tenant,
                $profile
            );

        return $subscription->refresh();
    }

    public function renew(
        Subscription $subscription,
        int $months = 1,
    ): Subscription {
        if (
            $subscription->status
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can be renewed.'
            );
        }

        if ($months <= 0) {
            throw new RuntimeException(
                'Renewal months must be greater than zero.'
            );
        }

        $currentEnd = CarbonImmutable::instance(
            $subscription->current_period_end
        );

        $newPeriod = new SubscriptionPeriod(
            $currentEnd,
            $currentEnd->addMonthsNoOverflow($months)
        );

        $subscription->update([
            'current_period_start' => $newPeriod->startsAt,
            'current_period_end' => $newPeriod->endsAt,
        ]);

        return $subscription->refresh();
    }
    public function expire(
        Subscription $subscription,
        ?CarbonImmutable $moment = null,
    ): Subscription {
        $moment ??= CarbonImmutable::now('UTC');

        if (
            $subscription->status
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can expire.'
            );
        }

        if (
            $moment->lessThan(
                CarbonImmutable::instance(
                    $subscription->current_period_end
                )
            )
        ) {
            throw new RuntimeException(
                'Subscription period has not expired.'
            );
        }

        $subscription->update([
            'status' => SubscriptionStatus::EXPIRED,
        ]);

        return $subscription->refresh();
    }

    private function assertMutable(
        Subscription $subscription
    ): void {
        if ($subscription->status->isTerminal()) {
            throw new RuntimeException(
                'Subscription is terminal.'
            );
        }
    }
}