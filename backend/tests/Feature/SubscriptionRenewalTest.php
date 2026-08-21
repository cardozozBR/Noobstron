<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use App\Support\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SubscriptionRenewalTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_can_be_renewed(): void
    {
        $subscription = $this->subscription();

        $renewed = app(
            SubscriptionService::class
        )->renew($subscription);

        $this->assertSame(
            '2026-09-18 00:00:00',
            $renewed->current_period_start
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-10-18 00:00:00',
            $renewed->current_period_end
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_custom_renewal_months_are_supported(): void
    {
        $renewed = app(
            SubscriptionService::class
        )->renew(
            $this->subscription(),
            3
        );

        $this->assertSame(
            '2026-12-18 00:00:00',
            $renewed->current_period_end
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_renewal_preserves_plan(): void
    {
        $subscription = $this->subscription();

        $planId = $subscription->plan_id;

        $renewed = app(
            SubscriptionService::class
        )->renew($subscription);

        $this->assertSame(
            $planId,
            $renewed->plan_id
        );
    }

    public function test_renewal_preserves_active_status(): void
    {
        $renewed = app(
            SubscriptionService::class
        )->renew(
            $this->subscription()
        );

        $this->assertSame(
            SubscriptionStatus::ACTIVE,
            $renewed->status
        );
    }

    public function test_zero_month_renewal_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionService::class
        )->renew(
            $this->subscription(),
            0
        );
    }

    public function test_negative_month_renewal_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionService::class
        )->renew(
            $this->subscription(),
            -1
        );
    }

    public function test_cancelled_subscription_cannot_be_renewed(): void
    {
        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->cancel(
            $this->subscription()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->renew($subscription);
    }

    public function test_suspended_subscription_cannot_be_renewed(): void
    {
        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->suspend(
            $this->subscription()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->renew($subscription);
    }

    public function test_expired_subscription_cannot_be_renewed(): void
    {
        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->expire(
            $this->subscription(),
            CarbonImmutable::parse(
                '2026-09-18 00:00:00 UTC'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->renew($subscription);
    }

    private function subscription(): Subscription
    {
        return app(
            SubscriptionService::class
        )->create(
            $this->tenant(),
            $this->plan(),
            new SubscriptionPeriod(
                CarbonImmutable::parse(
                    '2026-08-18 00:00:00 UTC'
                ),
                CarbonImmutable::parse(
                    '2026-09-18 00:00:00 UTC'
                )
            )
        );
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Renewal Tenant',
            'slug' => uniqid(
                'renewal-tenant-',
                true
            ),
            'status' => 'active',
        ]);
    }

    private function plan(): Plan
    {
        return Plan::query()->create([
            'code' => uniqid(
                'renewal-plan-',
                false
            ),
            'name' => 'Renewal Plan',
            'active' => true,
        ]);
    }
}