<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\AiUsageRecord;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\AiExecutionService;
use App\Services\TenantUsageService;
use App\Support\AiRequest;
use App\Support\AiResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class AiExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_calls_provider_after_guard_and_records_real_usage(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-success'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        $provider = new class implements AiProvider {
            public bool $executed = false;

            public function code(): string
            {
                return 'fake';
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                $this->executed = true;

                return new AiResult(
                    content: 'Generated answer',
                    model: 'fake-model',
                    inputTokens: 120,
                    outputTokens: 80,
                );
            }
        };

        $result = app(
            AiExecutionService::class
        )->execute(
            tenant: $tenant,
            provider: $provider,
            request: new AiRequest(
                prompt: 'Generate something',
                estimatedTokens: 500,
            ),
        );

        $this->assertTrue(
            $provider->executed
        );

        $this->assertSame(
            'Generated answer',
            $result->content
        );

        $this->assertSame(
            200,
            $result->totalTokens
        );

        $this->assertSame(
            200,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::AI_TOKENS
                )
        );

        $record = AiUsageRecord::query()
            ->withoutGlobalScopes()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->firstOrFail();

        $this->assertSame(
            'fake',
            $record->provider
        );

        $this->assertSame(
            'fake-model',
            $record->model
        );

        $this->assertSame(
            120,
            $record->input_tokens
        );

        $this->assertSame(
            80,
            $record->output_tokens
        );

        $this->assertSame(
            200,
            $record->total_tokens
        );
    }

    public function test_blocked_request_never_calls_provider(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-blocked'
            );

        $this->aiLimit(
            $plan,
            100
        );

        $provider = new class implements AiProvider {
            public bool $executed = false;

            public function code(): string
            {
                return 'fake';
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                $this->executed = true;

                return new AiResult(
                    content: 'Should not happen',
                    model: 'fake-model',
                    inputTokens: 1,
                    outputTokens: 1,
                );
            }
        };

        try {
            app(
                AiExecutionService::class
            )->execute(
                tenant: $tenant,
                provider: $provider,
                request: new AiRequest(
                    prompt: 'Blocked request',
                    estimatedTokens: 101,
                ),
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
        }

        $this->assertFalse(
            $provider->executed
        );

        $this->assertSame(
            0,
            AiUsageRecord::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count()
        );
    }

    public function test_estimated_tokens_are_not_recorded(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-estimate'
            );

        $this->aiLimit(
            $plan,
            10000
        );

        $provider = new class implements AiProvider {
            public function code(): string
            {
                return 'fake';
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                return new AiResult(
                    content: 'Small actual result',
                    model: 'fake-model',
                    inputTokens: 10,
                    outputTokens: 20,
                );
            }
        };

        app(
            AiExecutionService::class
        )->execute(
            tenant: $tenant,
            provider: $provider,
            request: new AiRequest(
                prompt: 'Large estimate',
                estimatedTokens: 5000,
            ),
        );

        $this->assertSame(
            30,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::AI_TOKENS
                )
        );
    }

    public function test_real_tokens_can_be_lower_than_estimate(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-lower'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        $provider = $this->provider(
            inputTokens: 100,
            outputTokens: 50
        );

        $result = app(
            AiExecutionService::class
        )->execute(
            tenant: $tenant,
            provider: $provider,
            request: new AiRequest(
                prompt: 'Estimate larger than actual',
                estimatedTokens: 900,
            ),
        );

        $this->assertSame(
            150,
            $result->totalTokens
        );

        $this->assertSame(
            150,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::AI_TOKENS
                )
        );
    }

    public function test_unlimited_plan_executes_and_records_usage(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-unlimited'
            );

        $this->aiLimit(
            $plan,
            null
        );

        $result = app(
            AiExecutionService::class
        )->execute(
            tenant: $tenant,
            provider: $this->provider(
                inputTokens: 100000,
                outputTokens: 200000
            ),
            request: new AiRequest(
                prompt: 'Enterprise request',
                estimatedTokens: 500000,
            ),
        );

        $this->assertSame(
            300000,
            $result->totalTokens
        );

        $this->assertSame(
            300000,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::AI_TOKENS
                )
        );
    }

    public function test_legacy_tenant_remains_compatible(): void
    {
        $tenant =
            $this->tenant(
                'ai-execution-legacy'
            );

        $result = app(
            AiExecutionService::class
        )->execute(
            tenant: $tenant,
            provider: $this->provider(
                inputTokens: 10,
                outputTokens: 5
            ),
            request: new AiRequest(
                prompt: 'Legacy request',
                estimatedTokens: 100,
            ),
        );

        $this->assertSame(
            15,
            $result->totalTokens
        );
    }

    public function test_provider_failure_does_not_record_usage(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-provider-failure'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        $provider = new class implements AiProvider {
            public function code(): string
            {
                return 'fake';
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                throw new RuntimeException(
                    'Provider failure.'
                );
            }
        };

        try {
            app(
                AiExecutionService::class
            )->execute(
                tenant: $tenant,
                provider: $provider,
                request: new AiRequest(
                    prompt: 'Failing request',
                    estimatedTokens: 100,
                ),
            );

            $this->fail(
                'Expected provider failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'Provider failure.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            0,
            AiUsageRecord::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count()
        );
    }

    public function test_blank_provider_code_is_rejected_before_execution(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-blank-provider'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        $provider = new class implements AiProvider {
            public bool $executed = false;

            public function code(): string
            {
                return '   ';
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                $this->executed = true;

                return new AiResult(
                    content: 'Never',
                    model: 'fake-model',
                    inputTokens: 1,
                    outputTokens: 1,
                );
            }
        };

        try {
            app(
                AiExecutionService::class
            )->execute(
                tenant: $tenant,
                provider: $provider,
                request: new AiRequest(
                    prompt: 'Invalid provider',
                    estimatedTokens: 10,
                ),
            );

            $this->fail(
                'Expected invalid provider failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'AI provider code is required.',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $provider->executed
        );
    }

    public function test_provider_code_is_normalized_before_recording(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'ai-execution-provider-normalized'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        $provider = new class implements AiProvider {
            public function code(): string
            {
                return '  TEST-PROVIDER  ';
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                return new AiResult(
                    content: 'Normalized',
                    model: 'model-one',
                    inputTokens: 5,
                    outputTokens: 5,
                );
            }
        };

        app(
            AiExecutionService::class
        )->execute(
            tenant: $tenant,
            provider: $provider,
            request: new AiRequest(
                prompt: 'Normalize provider',
                estimatedTokens: 20,
            ),
        );

        $record = AiUsageRecord::query()
            ->withoutGlobalScopes()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->firstOrFail();

        $this->assertSame(
            'test-provider',
            $record->provider
        );
    }

    public function test_execution_is_isolated_between_tenants(): void
    {
        [$firstTenant, $firstPlan] =
            $this->subscribedTenant(
                'ai-execution-first'
            );

        [$secondTenant, $secondPlan] =
            $this->subscribedTenant(
                'ai-execution-second'
            );

        $this->aiLimit(
            $firstPlan,
            1000
        );

        $this->aiLimit(
            $secondPlan,
            100
        );

        app(
            AiExecutionService::class
        )->execute(
            tenant: $firstTenant,
            provider: $this->provider(
                inputTokens: 100,
                outputTokens: 50
            ),
            request: new AiRequest(
                prompt: 'First tenant',
                estimatedTokens: 500,
            ),
        );

        try {
            app(
                AiExecutionService::class
            )->execute(
                tenant: $secondTenant,
                provider: $this->provider(
                    inputTokens: 10,
                    outputTokens: 10
                ),
                request: new AiRequest(
                    prompt: 'Second tenant',
                    estimatedTokens: 101,
                ),
            );

            $this->fail(
                'Expected second tenant blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );
        }

        $this->assertSame(
            150,
            app(TenantUsageService::class)
                ->value(
                    $firstTenant,
                    UsageMetric::AI_TOKENS
                )
        );

        $this->assertSame(
            0,
            app(TenantUsageService::class)
                ->value(
                    $secondTenant,
                    UsageMetric::AI_TOKENS
                )
        );
    }

    public function test_blank_prompt_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new AiRequest(
            prompt: '   ',
            estimatedTokens: 10,
        );
    }

    public function test_negative_estimated_tokens_are_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new AiRequest(
            prompt: 'Valid',
            estimatedTokens: -1,
        );
    }

    public function test_result_rejects_negative_input_tokens(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new AiResult(
            content: 'Invalid',
            model: 'fake-model',
            inputTokens: -1,
            outputTokens: 0,
        );
    }

    public function test_result_rejects_negative_output_tokens(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new AiResult(
            content: 'Invalid',
            model: 'fake-model',
            inputTokens: 0,
            outputTokens: -1,
        );
    }

    public function test_result_requires_model(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new AiResult(
            content: 'Invalid',
            model: '   ',
            inputTokens: 1,
            outputTokens: 1,
        );
    }

    private function provider(
        int $inputTokens,
        int $outputTokens
    ): AiProvider {
        return new class(
            $inputTokens,
            $outputTokens
        ) implements AiProvider {
            public function __construct(
                private readonly int $inputTokens,
                private readonly int $outputTokens,
            ) {
            }

            public function code(): string
            {
                return 'fake';
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                return new AiResult(
                    content: 'Fake result',
                    model: 'fake-model',
                    inputTokens:
                        $this->inputTokens,
                    outputTokens:
                        $this->outputTokens,
                );
            }
        };
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
}