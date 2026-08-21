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

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;


public function test_tenant_cannot_have_two_current_subscriptions(): void
{
    $tenant = \App\Models\Tenant::query()->create([
        'name' => 'Duplicate Subscription Tenant',
        'slug' => uniqid(
            'duplicate-subscription-',
            true
        ),
        'status' => 'active',
    ]);

    $plan = \App\Models\Plan::query()->create([
        'code' => uniqid(
            'duplicate-plan-',
            false
        ),
        'name' => 'Duplicate Plan',
        'active' => true,
    ]);

    $start = \Carbon\CarbonImmutable::parse(
        '2026-08-20 00:00:00 UTC'
    );

    $period = new \App\Support\SubscriptionPeriod(
        $start,
        $start->addMonthNoOverflow(),
    );

    $service = app(
        \App\Services\SubscriptionService::class
    );

    $service->create(
        $tenant,
        $plan,
        $period
    );

    $this->expectException(
        \RuntimeException::class
    );

    $this->expectExceptionMessage(
        'Tenant already has a current subscription.'
    );

    $service->create(
        $tenant,
        $plan,
        $period
    );
}


    public function test_subscription_can_be_created_active(): void
    {
        $subscription = app(
            SubscriptionService::class
        )->create(
            $this->tenant(),
            $this->plan(),
            $this->period()
        );

        $this->assertSame(
            SubscriptionStatus::ACTIVE,
            $subscription->status
        );
    }

    public function test_active_subscription_can_be_cancelled(): void
    {
        $subscription = $this->subscription();

        $subscription = app(
            SubscriptionService::class
        )->cancel($subscription);

        $this->assertSame(
            SubscriptionStatus::CANCELLED,
            $subscription->status
        );
    }

    public function test_active_subscription_can_be_suspended(): void
    {
        $subscription = app(
            SubscriptionService::class
        )->suspend(
            $this->subscription()
        );

        $this->assertSame(
            SubscriptionStatus::SUSPENDED,
            $subscription->status
        );
    }

    public function test_suspended_subscription_can_be_resumed(): void
    {
        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->suspend(
            $this->subscription()
        );

        $subscription = $service->resume(
            $subscription
        );

        $this->assertSame(
            SubscriptionStatus::ACTIVE,
            $subscription->status
        );
    }

    public function test_active_subscription_can_expire_at_period_end(): void
    {
        $subscription = $this->subscription();

        $subscription = app(
            SubscriptionService::class
        )->expire(
            $subscription,
            CarbonImmutable::parse(
                '2026-09-18 00:00:00 UTC'
            )
        );

        $this->assertSame(
            SubscriptionStatus::EXPIRED,
            $subscription->status
        );
    }

    public function test_subscription_cannot_expire_before_period_end(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionService::class
        )->expire(
            $this->subscription(),
            CarbonImmutable::parse(
                '2026-09-17 23:59:59 UTC'
            )
        );
    }

    public function test_terminal_subscription_cannot_be_cancelled_again(): void
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

        $service->cancel($subscription);
    }

    public function test_terminal_subscription_cannot_be_suspended(): void
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

        $service->suspend($subscription);
    }

    public function test_only_suspended_subscription_can_be_resumed(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionService::class
        )->resume(
            $this->subscription()
        );
    }

    private function subscription(): Subscription
    {
        return app(
            SubscriptionService::class
        )->create(
            $this->tenant(),
            $this->plan(),
            $this->period()
        );
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Subscription Service Tenant',
            'slug' => uniqid(
                'subscription-service-',
                true
            ),
            'status' => 'active',
        ]);
    }

    private function plan(): Plan
    {
        return Plan::query()->create([
            'code' => uniqid(
                'subscription-plan-',
                false
            ),
            'name' => 'Subscription Service Plan',
            'active' => true,
        ]);
    }

    private function period(): SubscriptionPeriod
    {
        return new SubscriptionPeriod(
            CarbonImmutable::parse(
                '2026-08-18 00:00:00 UTC'
            ),
            CarbonImmutable::parse(
                '2026-09-18 00:00:00 UTC'
            )
        );
    }

    public function test_subscription_creation_applies_plan_capability_profile(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Capability Bootstrap Tenant',
            'slug' => 'capability-bootstrap',
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => 'capability-bootstrap-plan',
            'name' => 'Capability Bootstrap Plan',
            'active' => true,
        ]);

        PlanFeature::query()->create([
            'plan_id' => $plan->id,
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 3,
        ]);

        PlanFeature::query()->create([
            'plan_id' => $plan->id,
            'feature' => Feature::AUDIT,
            'enabled' => false,
            'limit_value' => null,
        ]);

        app(SubscriptionService::class)->create(
            $tenant,
            $plan,
            new SubscriptionPeriod(
                CarbonImmutable::parse(
                    '2026-08-18 00:00:00 UTC'
                ),
                CarbonImmutable::parse(
                    '2026-09-18 00:00:00 UTC'
                ),
            ),
        );

        $this->assertDatabaseHas(
            'tenant_features',
            [
                'tenant_id' => $tenant->id,
                'feature' => Feature::USERS->value,
                'enabled' => true,
                'limit_value' => 3,
            ]
        );

        $this->assertDatabaseHas(
            'tenant_features',
            [
                'tenant_id' => $tenant->id,
                'feature' => Feature::AUDIT->value,
                'enabled' => false,
                'limit_value' => null,
            ]
        );
    }
}
