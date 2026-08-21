<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Models\AiUsageRecord;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\TenantUsageAllowanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantUsageAllowanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_below_limit_is_allowed(): void
    {
        $tenant = $this->tenant(
            'allowance-below'
        );

        $plan = $this->plan(
            'allowance-below-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $this->limit(
            $plan,
            UsageMetric::AI_TOKENS,
            1000
        );

        $this->aiUsage(
            $tenant,
            200,
            300
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertTrue(
            $result->available
        );

        $this->assertTrue(
            $result->allowed
        );

        $this->assertFalse(
            $result->unlimited
        );

        $this->assertSame(
            500,
            $result->used
        );

        $this->assertSame(
            1000,
            $result->limit
        );

        $this->assertSame(
            500,
            $result->remaining
        );

        $this->assertFalse(
            $result->exceeded
        );

        $this->assertFalse(
            $result->upgradeSuggested
        );
    }

    public function test_usage_at_limit_is_blocked(): void
    {
        $tenant = $this->tenant(
            'allowance-at-limit'
        );

        $plan = $this->plan(
            'allowance-at-limit-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $this->limit(
            $plan,
            UsageMetric::AI_TOKENS,
            100
        );

        $this->aiUsage(
            $tenant,
            40,
            60
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertFalse(
            $result->allowed
        );

        $this->assertTrue(
            $result->exceeded
        );

        $this->assertTrue(
            $result->upgradeSuggested
        );

        $this->assertSame(
            0,
            $result->remaining
        );
    }

    public function test_usage_above_limit_is_blocked(): void
    {
        $tenant = $this->tenant(
            'allowance-above-limit'
        );

        $plan = $this->plan(
            'allowance-above-limit-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $this->limit(
            $plan,
            UsageMetric::AI_TOKENS,
            100
        );

        $this->aiUsage(
            $tenant,
            80,
            70
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertSame(
            150,
            $result->used
        );

        $this->assertSame(
            100,
            $result->limit
        );

        $this->assertSame(
            0,
            $result->remaining
        );

        $this->assertFalse(
            $result->allowed
        );

        $this->assertTrue(
            $result->exceeded
        );

        $this->assertTrue(
            $result->upgradeSuggested
        );
    }

    public function test_zero_limit_is_immediately_blocked(): void
    {
        $tenant = $this->tenant(
            'allowance-zero'
        );

        $plan = $this->plan(
            'allowance-zero-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $this->limit(
            $plan,
            UsageMetric::AI_TOKENS,
            0
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertSame(
            0,
            $result->used
        );

        $this->assertSame(
            0,
            $result->limit
        );

        $this->assertSame(
            0,
            $result->remaining
        );

        $this->assertFalse(
            $result->allowed
        );

        $this->assertTrue(
            $result->exceeded
        );

        $this->assertTrue(
            $result->upgradeSuggested
        );
    }

    public function test_unlimited_usage_is_always_allowed(): void
    {
        $tenant = $this->tenant(
            'allowance-unlimited'
        );

        $plan = $this->plan(
            'allowance-enterprise'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $this->limit(
            $plan,
            UsageMetric::AI_TOKENS,
            null
        );

        $this->aiUsage(
            $tenant,
            5000000,
            5000000
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertTrue(
            $result->available
        );

        $this->assertTrue(
            $result->allowed
        );

        $this->assertTrue(
            $result->unlimited
        );

        $this->assertSame(
            10000000,
            $result->used
        );

        $this->assertNull(
            $result->limit
        );

        $this->assertNull(
            $result->remaining
        );

        $this->assertFalse(
            $result->exceeded
        );

        $this->assertFalse(
            $result->upgradeSuggested
        );
    }

    public function test_missing_limit_is_unavailable(): void
    {
        $tenant = $this->tenant(
            'allowance-missing-limit'
        );

        $plan = $this->plan(
            'allowance-missing-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertFalse(
            $result->available
        );

        $this->assertFalse(
            $result->allowed
        );

        $this->assertNull(
            $result->limit
        );

        $this->assertNull(
            $result->remaining
        );

        $this->assertFalse(
            $result->upgradeSuggested
        );

        $this->assertTrue(
            $result->plan->is($plan)
        );
    }

    public function test_tenant_without_active_subscription_is_unavailable(): void
    {
        $tenant = $this->tenant(
            'allowance-no-subscription'
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertFalse(
            $result->available
        );

        $this->assertFalse(
            $result->allowed
        );

        $this->assertNull(
            $result->plan
        );

        $this->assertFalse(
            $result->upgradeSuggested
        );
    }

    public function test_allowance_is_isolated_between_tenants(): void
    {
        $firstTenant = $this->tenant(
            'allowance-first'
        );

        $secondTenant = $this->tenant(
            'allowance-second'
        );

        $firstPlan = $this->plan(
            'allowance-first-plan'
        );

        $secondPlan = $this->plan(
            'allowance-second-plan'
        );

        $this->subscription(
            $firstTenant,
            $firstPlan
        );

        $this->subscription(
            $secondTenant,
            $secondPlan
        );

        $this->limit(
            $firstPlan,
            UsageMetric::AI_TOKENS,
            1000
        );

        $this->limit(
            $secondPlan,
            UsageMetric::AI_TOKENS,
            200
        );

        $this->aiUsage(
            $firstTenant,
            100,
            100
        );

        $this->aiUsage(
            $secondTenant,
            150,
            100
        );

        $service = app(
            TenantUsageAllowanceService::class
        );

        $firstResult = $service->resolve(
            $firstTenant,
            UsageMetric::AI_TOKENS
        );

        $secondResult = $service->resolve(
            $secondTenant,
            UsageMetric::AI_TOKENS
        );

        $this->assertTrue(
            $firstResult->allowed
        );

        $this->assertSame(
            800,
            $firstResult->remaining
        );

        $this->assertFalse(
            $secondResult->allowed
        );

        $this->assertSame(
            0,
            $secondResult->remaining
        );
    }

    public function test_users_metric_remains_unavailable_in_usage_allowance(): void
    {
        $tenant = $this->tenant(
            'allowance-users'
        );

        $plan = $this->plan(
            'allowance-users-plan'
        );

        $this->subscription(
            $tenant,
            $plan
        );

        $result = app(
            TenantUsageAllowanceService::class
        )->resolve(
            $tenant,
            UsageMetric::USERS
        );

        $this->assertFalse(
            $result->available
        );

        $this->assertFalse(
            $result->allowed
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

    private function limit(
        Plan $plan,
        UsageMetric $metric,
        ?int $limit
    ): void {
        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' => $metric,
            'limit_value' => $limit,
        ]);
    }

    private function aiUsage(
        Tenant $tenant,
        int $input,
        int $output
    ): void {
        AiUsageRecord::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'provider' => 'test',
            'model' => 'test-model',
            'operation' => 'test',
            'input_tokens' => $input,
            'output_tokens' => $output,
            'total_tokens' =>
                $input + $output,
        ]);
    }
}