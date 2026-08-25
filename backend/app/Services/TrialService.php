<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant;
use App\Support\TrialPeriod;
use Carbon\CarbonImmutable;
use RuntimeException;

class TrialService
{
    public function __construct(
        private readonly SubscriptionBillingService $billing,
    ) {}

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public function start(
        Tenant $tenant,
        ?CarbonImmutable $startsAt = null,
        int $days = TrialPeriod::DEFAULT_DAYS,
    ): Tenant {
        if (
            $tenant->trial_started_at !== null
            || $tenant->trial_ends_at !== null
        ) {
            throw new RuntimeException(
                'Trial has already been initialized.'
            );
        }

        $startsAt ??= CarbonImmutable::now('UTC');

        $period = TrialPeriod::start(
            $startsAt,
            $days
        );

        $tenant->forceFill([
            'trial_started_at' => $period->startsAt(),
            'trial_ends_at' => $period->endsAt(),
        ])->save();

        return $tenant->refresh();
    }

    public function extend(
        Tenant $tenant,
        int $days,
        ?CarbonImmutable $moment = null,
    ): Tenant {
        if ($days < 1 || $days > 90) {
            throw new RuntimeException(
                'Trial extension must be between 1 and 90 days.'
            );
        }

        if (
            $tenant->trial_started_at === null
            || $tenant->trial_ends_at === null
        ) {
            throw new RuntimeException(
                'Trial has not been initialized.'
            );
        }

        $moment ??= CarbonImmutable::now('UTC');

        $currentEnd = CarbonImmutable::instance(
            $tenant->trial_ends_at
        );

        $base = $currentEnd->greaterThan($moment)
            ? $currentEnd
            : $moment;

        $tenant->forceFill([
            'trial_ends_at' => $base->addDays($days),
        ])->save();

        return $tenant->refresh();
    }

    public function status(
        Tenant $tenant,
        ?CarbonImmutable $moment = null,
    ): string {
        if (
            $tenant->trial_started_at === null
            && $tenant->trial_ends_at === null
        ) {
            return self::STATUS_NOT_STARTED;
        }

        if (
            $tenant->trial_started_at === null
            || $tenant->trial_ends_at === null
        ) {
            throw new RuntimeException(
                'Trial persistence is inconsistent.'
            );
        }

        $moment ??= CarbonImmutable::now('UTC');

        $period = new TrialPeriod(
            CarbonImmutable::instance(
                $tenant->trial_started_at
            ),
            CarbonImmutable::instance(
                $tenant->trial_ends_at
            ),
        );

        if ($period->isExpiredAt($moment)) {
            return self::STATUS_EXPIRED;
        }

        if ($period->isActiveAt($moment)) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_NOT_STARTED;
    }

    public function isExpired(
        Tenant $tenant,
        ?CarbonImmutable $moment = null,
    ): bool {
        return $this->status(
            $tenant,
            $moment
        ) === self::STATUS_EXPIRED;
    }

    public function blockIfExpired(
        Tenant $tenant,
        ?CarbonImmutable $moment = null,
    ): bool {
        if (! $this->isExpired($tenant, $moment)) {
            return false;
        }

        $paidSubscription = $tenant
            ->subscriptions()
            ->where(
                'status',
                SubscriptionStatus::ACTIVE->value
            )
            ->whereNotNull('paid_at')
            ->latest('id')
            ->first();

        if (
            $paidSubscription !== null
            && $this->billing->isPaid($paidSubscription)
        ) {
            return false;
        }

        if ($tenant->status !== 'active') {
            return false;
        }

        $tenant->forceFill([
            'status' => 'blocked',
        ])->save();

        return true;
    }
}
