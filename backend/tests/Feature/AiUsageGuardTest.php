<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\AiUsageGuard;
use App\Services\AiUsageRecorder;
use App\Services\TenantUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class AiUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_request_is_allowed_below_token_limit(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-allowed'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            500
        );

        $this->addToAssertionCount(1);
    }

    public function test_ai_request_exactly_to_limit_is_allowed(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-exact'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        $this->recordUsage(
            $tenant,
            900
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            100
        );

        $this->addToAssertionCount(1);
    }

    public function test_projected_ai_tokens_above_limit_are_blocked(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-projected'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        $this->recordUsage(
            $tenant,
            900
        );

        try {
            app(
                AiUsageGuard::class
            )->assertCanRequest(
                $tenant,
                101
            );

            $this->fail(
                'Expected UsageBlockedException.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
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
                $exception->plan->is(
                    $plan
                )
            );
        }
    }

    public function test_existing_ai_usage_is_included_in_projection(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-existing'
            );

        $this->aiLimit(
            $plan,
            500
        );

        $this->recordUsage(
            $tenant,
            450
        );

        try {
            app(
                AiUsageGuard::class
            )->assertCanRequest(
                $tenant,
                51
            );

            $this->fail(
                'Expected existing usage to block request.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                450,
                $exception->used
            );

            $this->assertSame(
                51,
                $exception->requested
            );

            $this->assertSame(
                50,
                $exception->remaining
            );
        }
    }

    public function test_zero_ai_limit_blocks_positive_request(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-zero'
            );

        $this->aiLimit(
            $plan,
            0
        );

        $this->expectException(
            UsageBlockedException::class
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            1
        );
    }

    public function test_unlimited_ai_usage_allows_request(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-unlimited'
            );

        $this->aiLimit(
            $plan,
            null
        );

        $this->recordUsage(
            $tenant,
            10000000
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            50000000
        );

        $this->addToAssertionCount(1);
    }

    public function test_zero_estimate_is_noop(): void
    {
        $tenant =
            $this->tenant(
                'ai-guard-zero-estimate'
            );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            0
        );

        $this->addToAssertionCount(1);
    }

    public function test_negative_estimate_is_rejected(): void
    {
        $tenant =
            $this->tenant(
                'ai-guard-negative'
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            -1
        );
    }

    public function test_ai_guard_is_isolated_between_tenants(): void
    {
        [$blockedTenant, $blockedPlan] =
            $this->subscribedTenant(
                'ai-guard-blocked-tenant'
            );

        [$allowedTenant, $allowedPlan] =
            $this->subscribedTenant(
                'ai-guard-allowed-tenant'
            );

        $this->aiLimit(
            $blockedPlan,
            100
        );

        $this->aiLimit(
            $allowedPlan,
            1000
        );

        $this->recordUsage(
            $blockedTenant,
            100
        );

        $this->recordUsage(
            $allowedTenant,
            100
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $allowedTenant,
            500
        );

        $this->addToAssertionCount(1);

        $this->expectException(
            UsageBlockedException::class
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $blockedTenant,
            1
        );
    }

    public function test_legacy_tenant_without_subscription_remains_compatible(): void
    {
        $tenant =
            $this->tenant(
                'ai-guard-legacy'
            );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            1000
        );

        $this->addToAssertionCount(1);
    }

    public function test_cancelled_subscription_does_not_receive_legacy_ai_access(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-cancelled'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        DB::table('subscriptions')
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->update([
                'status' =>
                    SubscriptionStatus::CANCELLED
                        ->value,
            ]);

        $this->expectException(
            UsageBlockedException::class
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            100
        );
    }

    public function test_ai_guard_does_not_record_estimated_tokens(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-no-reservation'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            500
        );

        $this->assertSame(
            0,
            app(
                TenantUsageService::class
            )->value(
                $tenant,
                UsageMetric::AI_TOKENS
            )
        );
    }

    public function test_real_usage_is_recorded_only_after_recorder_runs(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-guard-real-consumption'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            AiUsageGuard::class
        )->assertCanRequest(
            $tenant,
            500
        );

        $this->assertSame(
            0,
            app(
                TenantUsageService::class
            )->value(
                $tenant,
                UsageMetric::AI_TOKENS
            )
        );

        app(
            AiUsageRecorder::class
        )->record(
            tenant: $tenant,
            provider: 'test-provider',
            model: 'test-model',
            inputTokens: 200,
            outputTokens: 100,
        );

        $this->assertSame(
            300,
            app(
                TenantUsageService::class
            )->value(
                $tenant,
                UsageMetric::AI_TOKENS
            )
        );
    }

    private function subscribedTenant(
        string $slug
    ): array {
        $tenant =
            $this->tenant(
                $slug
            );

        $plan =
            Plan::query()->create([
                'code' =>
                    $slug . '-plan',
                'name' =>
                    ucfirst($slug)
                    . ' Plan',
                'active' =>
                    true,
            ]);

        DB::table(
            'subscriptions'
        )->insert([
            'tenant_id' =>
                $tenant->id,
            'plan_id' =>
                $plan->id,
            'status' =>
                SubscriptionStatus::ACTIVE
                    ->value,
            'current_period_start' =>
                '2026-08-18 00:00:00',
            'current_period_end' =>
                '2026-09-18 00:00:00',
            'created_at' =>
                now(),
            'updated_at' =>
                now(),
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
            'name' =>
                ucfirst($slug),
            'slug' =>
                $slug,
            'status' =>
                'active',
            'country_code' =>
                'BR',
            'locale' =>
                'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' =>
                'BRL',
        ]);
    }

    private function aiLimit(
        Plan $plan,
        ?int $limit
    ): void {
        PlanUsageLimit::query()->create([
            'plan_id' =>
                $plan->id,
            'metric' =>
                UsageMetric::AI_TOKENS,
            'limit_value' =>
                $limit,
        ]);
    }

    private function recordUsage(
        Tenant $tenant,
        int $tokens
    ): void {
        app(
            AiUsageRecorder::class
        )->record(
            tenant: $tenant,
            provider: 'test-provider',
            model: 'test-model',
            inputTokens: $tokens,
            outputTokens: 0,
        );
    }
}