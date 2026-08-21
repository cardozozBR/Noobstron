<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use App\Support\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SubscriptionPlanChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_can_change_plan(): void
    {
        $subscription = $this->subscription(
            $this->plan('start')
        );

        $newPlan = $this->plan('pro');

        $subscription = app(
            SubscriptionService::class
        )->changePlan(
            $subscription,
            $newPlan
        );

        $this->assertSame(
            $newPlan->id,
            $subscription->plan_id
        );
    }

    public function test_plan_change_applies_new_capability_profile(): void
    {
        $start = $this->plan('start-profile');

        PlanFeature::query()->create([
            'plan_id' => $start->id,
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 3,
        ]);

        $pro = $this->plan('pro-profile');

        PlanFeature::query()->create([
            'plan_id' => $pro->id,
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 10,
        ]);

        $subscription = $this->subscription(
            $start
        );

        app(
            SubscriptionService::class
        )->changePlan(
            $subscription,
            $pro
        );

        $this->assertDatabaseHas(
            'tenant_features',
            [
                'tenant_id' => $subscription->tenant_id,
                'feature' => Feature::USERS->value,
                'limit_value' => 10,
            ]
        );
    }

    public function test_same_plan_change_is_idempotent(): void
    {
        $plan = $this->plan('same-plan');

        $subscription = $this->subscription(
            $plan
        );

        $result = app(
            SubscriptionService::class
        )->changePlan(
            $subscription,
            $plan
        );

        $this->assertSame(
            $plan->id,
            $result->plan_id
        );
    }

    public function test_cancelled_subscription_cannot_change_plan(): void
    {
        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->cancel(
            $this->subscription(
                $this->plan('cancelled-plan')
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->changePlan(
            $subscription,
            $this->plan('target-plan')
        );
    }

    public function test_suspended_subscription_cannot_change_plan(): void
    {
        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->suspend(
            $this->subscription(
                $this->plan('suspended-plan')
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->changePlan(
            $subscription,
            $this->plan('suspended-target')
        );
    }

    public function test_expired_subscription_cannot_change_plan(): void
    {
        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->expire(
            $this->subscription(
                $this->plan('expired-plan')
            ),
            CarbonImmutable::parse(
                '2026-09-18 00:00:00 UTC'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->changePlan(
            $subscription,
            $this->plan('expired-target')
        );
    }

    private function subscription(
        Plan $plan
    ): Subscription {
        return app(
            SubscriptionService::class
        )->create(
            $this->tenant(),
            $plan,
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
            'name' => 'Plan Change Tenant',
            'slug' => uniqid(
                'plan-change-',
                true
            ),
            'status' => 'active',
        ]);
    }

    private function plan(
        string $code
    ): Plan {
        return Plan::query()->create([
            'code' => $code,
            'name' => ucfirst($code),
            'active' => true,
        ]);
    }
}