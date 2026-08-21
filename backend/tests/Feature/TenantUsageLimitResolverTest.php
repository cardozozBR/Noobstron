<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\TenantUsageLimitResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantUsageLimitResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_resolves_numeric_limit(): void
    {
        $tenant = $this->tenant(
            'usage-resolver-limited'
        );

        $plan = $this->plan(
            'usage-resolver-start'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' =>
                UsageMetric::MESSAGES,
            'limit_value' => 1000,
        ]);

        $result = app(
            TenantUsageLimitResolver::class
        )->resolve(
            $tenant,
            UsageMetric::MESSAGES
        );

        $this->assertTrue(
            $result->available
        );

        $this->assertFalse(
            $result->unlimited
        );

        $this->assertTrue(
            $result->isLimited()
        );

        $this->assertSame(
            1000,
            $result->limit
        );

        $this->assertTrue(
            $result->plan->is($plan)
        );
    }

    public function test_zero_limit_is_preserved(): void
    {
        $tenant = $this->tenant(
            'usage-resolver-zero'
        );

        $plan = $this->plan(
            'usage-resolver-zero-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' =>
                UsageMetric::AI_TOKENS,
            'limit_value' => 0,
        ]);

        $result = app(
            TenantUsageLimitResolver::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertTrue(
            $result->available
        );

        $this->assertFalse(
            $result->unlimited
        );

        $this->assertSame(
            0,
            $result->limit
        );
    }

    public function test_null_limit_is_unlimited(): void
    {
        $tenant = $this->tenant(
            'usage-resolver-unlimited'
        );

        $plan = $this->plan(
            'usage-resolver-enterprise'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' =>
                UsageMetric::STORAGE_BYTES,
            'limit_value' => null,
        ]);

        $result = app(
            TenantUsageLimitResolver::class
        )->resolve(
            $tenant,
            UsageMetric::STORAGE_BYTES
        );

        $this->assertTrue(
            $result->available
        );

        $this->assertTrue(
            $result->unlimited
        );

        $this->assertFalse(
            $result->isLimited()
        );

        $this->assertNull(
            $result->limit
        );

        $this->assertTrue(
            $result->plan->is($plan)
        );
    }

    public function test_tenant_without_active_subscription_is_unavailable(): void
    {
        $tenant = $this->tenant(
            'usage-resolver-no-subscription'
        );

        $result = app(
            TenantUsageLimitResolver::class
        )->resolve(
            $tenant,
            UsageMetric::MESSAGES
        );

        $this->assertFalse(
            $result->available
        );

        $this->assertFalse(
            $result->unlimited
        );

        $this->assertNull(
            $result->limit
        );

        $this->assertNull(
            $result->plan
        );
    }

    public function test_cancelled_subscription_is_not_active_plan(): void
    {
        $tenant = $this->tenant(
            'usage-resolver-cancelled'
        );

        $plan = $this->plan(
            'usage-resolver-cancelled-plan'
        );

        $this->subscription(
            $tenant,
            $plan,
            SubscriptionStatus::CANCELLED
        );

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' =>
                UsageMetric::MESSAGES,
            'limit_value' => 1000,
        ]);

        $result = app(
            TenantUsageLimitResolver::class
        )->resolve(
            $tenant,
            UsageMetric::MESSAGES
        );

        $this->assertFalse(
            $result->available
        );

        $this->assertNull(
            $result->plan
        );
    }

    public function test_missing_metric_is_unavailable_for_active_plan(): void
    {
        $tenant = $this->tenant(
            'usage-resolver-missing'
        );

        $plan = $this->plan(
            'usage-resolver-missing-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $result = app(
            TenantUsageLimitResolver::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertFalse(
            $result->available
        );

        $this->assertFalse(
            $result->unlimited
        );

        $this->assertNull(
            $result->limit
        );

        $this->assertTrue(
            $result->plan->is($plan)
        );
    }

    public function test_usage_limits_are_isolated_between_tenants(): void
    {
        $firstTenant = $this->tenant(
            'usage-resolver-first'
        );

        $secondTenant = $this->tenant(
            'usage-resolver-second'
        );

        $start = $this->plan(
            'usage-resolver-first-plan'
        );

        $pro = $this->plan(
            'usage-resolver-second-plan'
        );

        $this->subscription(
            $firstTenant,
            $start
        );

        $this->subscription(
            $secondTenant,
            $pro
        );

        PlanUsageLimit::query()->create([
            'plan_id' => $start->id,
            'metric' =>
                UsageMetric::MESSAGES,
            'limit_value' => 1000,
        ]);

        PlanUsageLimit::query()->create([
            'plan_id' => $pro->id,
            'metric' =>
                UsageMetric::MESSAGES,
            'limit_value' => 10000,
        ]);

        $resolver = app(
            TenantUsageLimitResolver::class
        );

        $firstResult = $resolver->resolve(
            $firstTenant,
            UsageMetric::MESSAGES
        );

        $secondResult = $resolver->resolve(
            $secondTenant,
            UsageMetric::MESSAGES
        );

        $this->assertSame(
            1000,
            $firstResult->limit
        );

        $this->assertSame(
            10000,
            $secondResult->limit
        );

        $this->assertTrue(
            $firstResult->plan->is($start)
        );

        $this->assertTrue(
            $secondResult->plan->is($pro)
        );
    }

    public function test_users_metric_remains_outside_plan_usage_limits(): void
    {
        $tenant = $this->tenant(
            'usage-resolver-users'
        );

        $plan = $this->plan(
            'usage-resolver-users-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $result = app(
            TenantUsageLimitResolver::class
        )->resolve(
            $tenant,
            UsageMetric::USERS
        );

        $this->assertFalse(
            $result->available
        );

        $this->assertTrue(
            $result->plan->is($plan)
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
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

    private function subscription(
        Tenant $tenant,
        Plan $plan,
        SubscriptionStatus $status =
            SubscriptionStatus::ACTIVE
    ): void {
        DB::table('subscriptions')->insert([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status->value,
            'current_period_start' =>
                '2026-08-18 00:00:00',
            'current_period_end' =>
                '2026-09-18 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}