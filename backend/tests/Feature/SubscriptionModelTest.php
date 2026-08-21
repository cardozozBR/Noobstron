<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_belongs_to_tenant_and_plan(): void
    {
        $tenant = $this->tenant();

        $plan = $this->plan(
            'subscription-plan'
        );

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_start' => '2026-08-18 00:00:00',
            'current_period_end' => '2026-09-18 00:00:00',
        ]);

        $this->assertTrue(
            $subscription->tenant->is($tenant)
        );

        $this->assertTrue(
            $subscription->plan->is($plan)
        );
    }

    public function test_status_is_cast_to_enum(): void
    {
        $subscription = $this->subscription();

        $this->assertSame(
            SubscriptionStatus::ACTIVE,
            $subscription->status
        );
    }

    public function test_period_dates_are_immutable_datetimes(): void
    {
        $subscription = $this->subscription();

        $this->assertSame(
            '2026-08-18 00:00:00',
            $subscription->current_period_start
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-18 00:00:00',
            $subscription->current_period_end
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_default_status_is_active(): void
    {
        $tenant = $this->tenant();

        $plan = $this->plan(
            'default-status'
        );

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'current_period_start' => '2026-08-18 00:00:00',
            'current_period_end' => '2026-09-18 00:00:00',
        ]);

        $subscription->refresh();

        $this->assertSame(
            SubscriptionStatus::ACTIVE,
            $subscription->status
        );
    }

    public function test_subscription_can_be_cancelled(): void
    {
        $subscription = $this->subscription();

        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
        ]);

        $this->assertSame(
            SubscriptionStatus::CANCELLED,
            $subscription->refresh()->status
        );
    }

    public function test_subscription_can_be_suspended(): void
    {
        $subscription = $this->subscription();

        $subscription->update([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);

        $this->assertSame(
            SubscriptionStatus::SUSPENDED,
            $subscription->refresh()->status
        );
    }

    public function test_subscription_can_expire(): void
    {
        $subscription = $this->subscription();

        $subscription->update([
            'status' => SubscriptionStatus::EXPIRED,
        ]);

        $this->assertSame(
            SubscriptionStatus::EXPIRED,
            $subscription->refresh()->status
        );
    }

    private function subscription(): Subscription
    {
        return Subscription::query()->create([
            'tenant_id' => $this->tenant()->id,
            'plan_id' => $this->plan(
                uniqid('plan-', true)
            )->id,
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_start' => '2026-08-18 00:00:00',
            'current_period_end' => '2026-09-18 00:00:00',
        ]);
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Subscription Tenant',
            'slug' => uniqid(
                'subscription-tenant-',
                true
            ),
            'status' => 'active',
        ]);
    }

    private function plan(
        string $code
    ): Plan {
        return Plan::query()->create([
            'code' => strtolower(
                preg_replace(
                    '/[^a-z0-9_-]+/',
                    '-',
                    $code
                )
            ),
            'name' => 'Subscription Plan',
            'active' => true,
        ]);
    }
}