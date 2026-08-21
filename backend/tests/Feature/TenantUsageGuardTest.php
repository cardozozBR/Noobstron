<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\AiUsageRecord;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\TenantUsageGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class TenantUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumption_below_limit_is_allowed(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'usage-guard-below'
            );

        $this->limit(
            $plan,
            1000
        );

        $this->usage(
            $tenant,
            400
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $tenant,
                UsageMetric::AI_TOKENS,
                500
            );

        $this->addToAssertionCount(1);
    }

    public function test_consumption_exactly_to_limit_is_allowed(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'usage-guard-exact'
            );

        $this->limit(
            $plan,
            1000
        );

        $this->usage(
            $tenant,
            900
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $tenant,
                UsageMetric::AI_TOKENS,
                100
            );

        $this->addToAssertionCount(1);
    }

    public function test_projected_usage_above_limit_is_blocked(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'usage-guard-projected'
            );

        $this->limit(
            $plan,
            1000
        );

        $this->usage(
            $tenant,
            900
        );

        try {
            app(TenantUsageGuard::class)
                ->assertCanConsume(
                    $tenant,
                    UsageMetric::AI_TOKENS,
                    101
                );

            $this->fail(
                'Expected usage blocking exception.'
            );
        } catch (UsageBlockedException $exception) {
            $this->assertSame(
                UsageMetric::AI_TOKENS,
                $exception->metric
            );

            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );

            $this->assertSame(
                900,
                $exception->used
            );

            $this->assertSame(
                101,
                $exception->requested
            );

            $this->assertSame(
                1000,
                $exception->limit
            );

            $this->assertSame(
                100,
                $exception->remaining
            );

            $this->assertTrue(
                $exception->upgradeSuggested
            );

            $this->assertTrue(
                $exception->plan->is($plan)
            );
        }
    }

    public function test_tenant_already_at_limit_is_blocked(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'usage-guard-at-limit'
            );

        $this->limit(
            $plan,
            100
        );

        $this->usage(
            $tenant,
            100
        );

        $this->expectException(
            UsageBlockedException::class
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $tenant,
                UsageMetric::AI_TOKENS,
                1
            );
    }

    public function test_zero_limit_blocks_positive_consumption(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'usage-guard-zero'
            );

        $this->limit(
            $plan,
            0
        );

        try {
            app(TenantUsageGuard::class)
                ->assertCanConsume(
                    $tenant,
                    UsageMetric::AI_TOKENS,
                    1
                );

            $this->fail(
                'Expected usage blocking exception.'
            );
        } catch (UsageBlockedException $exception) {
            $this->assertSame(
                0,
                $exception->limit
            );

            $this->assertSame(
                0,
                $exception->remaining
            );

            $this->assertTrue(
                $exception->upgradeSuggested
            );
        }
    }

    public function test_unlimited_plan_allows_consumption(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'usage-guard-unlimited'
            );

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' =>
                UsageMetric::AI_TOKENS,
            'limit_value' => null,
        ]);

        $this->usage(
            $tenant,
            10000000
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $tenant,
                UsageMetric::AI_TOKENS,
                50000000
            );

        $this->addToAssertionCount(1);
    }

    public function test_missing_limit_is_blocked_as_unavailable(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'usage-guard-missing'
            );

        try {
            app(TenantUsageGuard::class)
                ->assertCanConsume(
                    $tenant,
                    UsageMetric::AI_TOKENS,
                    1
                );

            $this->fail(
                'Expected unavailable usage exception.'
            );
        } catch (UsageBlockedException $exception) {
            $this->assertSame(
                'unavailable',
                $exception->reason
            );

            $this->assertFalse(
                $exception->upgradeSuggested
            );

            $this->assertNull(
                $exception->limit
            );

            $this->assertTrue(
                $exception->plan->is($plan)
            );
        }
    }

    public function test_tenant_without_subscription_is_blocked_as_unavailable(): void
    {
        $tenant = $this->tenant(
            'usage-guard-no-subscription'
        );

        try {
            app(TenantUsageGuard::class)
                ->assertCanConsume(
                    $tenant,
                    UsageMetric::AI_TOKENS,
                    1
                );

            $this->fail(
                'Expected unavailable usage exception.'
            );
        } catch (UsageBlockedException $exception) {
            $this->assertSame(
                'unavailable',
                $exception->reason
            );

            $this->assertNull(
                $exception->plan
            );

            $this->assertFalse(
                $exception->upgradeSuggested
            );
        }
    }

    public function test_zero_consumption_is_always_noop(): void
    {
        $tenant = $this->tenant(
            'usage-guard-zero-consumption'
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $tenant,
                UsageMetric::AI_TOKENS,
                0
            );

        $this->addToAssertionCount(1);
    }

    public function test_negative_consumption_is_rejected(): void
    {
        $tenant = $this->tenant(
            'usage-guard-negative'
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $tenant,
                UsageMetric::AI_TOKENS,
                -1
            );
    }

    public function test_guard_is_isolated_between_tenants(): void
    {
        [$firstTenant, $firstPlan] =
            $this->subscribedTenant(
                'usage-guard-first'
            );

        [$secondTenant, $secondPlan] =
            $this->subscribedTenant(
                'usage-guard-second'
            );

        $this->limit(
            $firstPlan,
            1000
        );

        $this->limit(
            $secondPlan,
            100
        );

        $this->usage(
            $firstTenant,
            100
        );

        $this->usage(
            $secondTenant,
            100
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $firstTenant,
                UsageMetric::AI_TOKENS,
                500
            );

        $this->addToAssertionCount(1);

        $this->expectException(
            UsageBlockedException::class
        );

        app(TenantUsageGuard::class)
            ->assertCanConsume(
                $secondTenant,
                UsageMetric::AI_TOKENS,
                1
            );
    }

    private function subscribedTenant(
        string $slug
    ): array {
        $tenant = $this->tenant(
            $slug
        );

        $plan = Plan::query()->create([
            'code' => $slug . '-plan',
            'name' => ucfirst($slug) . ' Plan',
            'active' => true,
        ]);

        DB::table('subscriptions')->insert([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' =>
                SubscriptionStatus::ACTIVE->value,
            'current_period_start' =>
                '2026-08-18 00:00:00',
            'current_period_end' =>
                '2026-09-18 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            $tenant,
            $plan,
        ];
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

    private function limit(
        Plan $plan,
        int $limit
    ): void {
        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' =>
                UsageMetric::AI_TOKENS,
            'limit_value' => $limit,
        ]);
    }

    private function usage(
        Tenant $tenant,
        int $tokens
    ): void {
        AiUsageRecord::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'provider' => 'test',
            'model' => 'test-model',
            'operation' => 'usage-guard',
            'input_tokens' => $tokens,
            'output_tokens' => 0,
            'total_tokens' => $tokens,
        ]);
    }
}