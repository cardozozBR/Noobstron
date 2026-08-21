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
use App\Services\AiConfiguredExecutionService;
use App\Services\AiProviderRegistry;
use App\Services\TenantUsageService;
use App\Support\AiRequest;
use App\Support\AiResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AiConfiguredExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_configured_provider_is_resolved_and_executed(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-default'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.default',
            'fake'
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,

                'model' =>
                    'configured-model',

                'parameters' => [
                    'temperature' =>
                        0.3,
                ],
            ]
        );

        $provider =
            new class implements AiProvider {
                public ?AiRequest $received =
                    null;

                public function code(): string
                {
                    return 'fake';
                }

                public function execute(
                    AiRequest $request
                ): AiResult {
                    $this->received =
                        $request;

                    return new AiResult(
                        content:
                            'Configured result',
                        model:
                            $request->model,
                        inputTokens:
                            100,
                        outputTokens:
                            50,
                    );
                }
            };

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        $result = app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,
            request:
                new AiRequest(
                    prompt:
                        'Configured request',
                    estimatedTokens:
                        500,
                ),
        );

        $this->assertSame(
            'Configured result',
            $result->content
        );

        $this->assertNotNull(
            $provider->received
        );

        $this->assertSame(
            'configured-model',
            $provider->received->model
        );

        $this->assertSame(
            0.3,
            $provider->received
                ->parameter(
                    'temperature'
                )
        );

        $this->assertSame(
            150,
            app(
                TenantUsageService::class
            )->value(
                $tenant,
                UsageMetric::AI_TOKENS
            )
        );
    }

    public function test_explicit_provider_overrides_default_provider(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-explicit'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.default',
            'first'
        );

        config()->set(
            'ai.providers.first',
            [
                'enabled' =>
                    true,
                'model' =>
                    'first-model',
                'parameters' =>
                    [],
            ]
        );

        config()->set(
            'ai.providers.second',
            [
                'enabled' =>
                    true,
                'model' =>
                    'second-model',
                'parameters' =>
                    [],
            ]
        );

        $first =
            $this->provider(
                'first'
            );

        $second =
            $this->provider(
                'second'
            );

        $registry = app(
            AiProviderRegistry::class
        );

        $registry->register(
            $first
        );

        $registry->register(
            $second
        );

        $result = app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,
            request:
                new AiRequest(
                    prompt:
                        'Explicit provider',
                    estimatedTokens:
                        100,
                ),
            provider:
                ' SECOND ',
        );

        $this->assertSame(
            'second-model',
            $result->model
        );
    }

    public function test_disabled_provider_is_rejected_before_provider_execution(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-disabled'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    false,
                'model' =>
                    'fake-model',
                'parameters' =>
                    [],
            ]
        );

        $provider =
            new class implements AiProvider {
                public bool $executed =
                    false;

                public function code(): string
                {
                    return 'fake';
                }

                public function execute(
                    AiRequest $request
                ): AiResult {
                    $this->executed =
                        true;

                    return new AiResult(
                        content:
                            'Never',
                        model:
                            'fake-model',
                        inputTokens:
                            1,
                        outputTokens:
                            1,
                    );
                }
            };

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        try {
            app(
                AiConfiguredExecutionService::class
            )->execute(
                tenant:
                    $tenant,
                request:
                    new AiRequest(
                        prompt:
                            'Disabled',
                        estimatedTokens:
                            10,
                    ),
                provider:
                    'fake',
            );

            $this->fail(
                'Expected disabled provider failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'AI provider [fake] is disabled.',
                $exception->getMessage()
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

    public function test_unregistered_configured_provider_is_rejected(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-unregistered'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.providers.missing',
            [
                'enabled' =>
                    true,
                'model' =>
                    'missing-model',
                'parameters' =>
                    [],
            ]
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,
            request:
                new AiRequest(
                    prompt:
                        'Missing provider',
                    estimatedTokens:
                        10,
                ),
            provider:
                'missing',
        );
    }

    public function test_configuration_model_and_parameters_are_delivered_to_provider(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-request'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,

                'model' =>
                    'model-from-config',

                'parameters' => [
                    'temperature' =>
                        0.7,

                    'max_output_tokens' =>
                        400,
                ],
            ]
        );

        $provider =
            new class implements AiProvider {
                public ?AiRequest $received =
                    null;

                public function code(): string
                {
                    return 'fake';
                }

                public function execute(
                    AiRequest $request
                ): AiResult {
                    $this->received =
                        $request;

                    return new AiResult(
                        content:
                            'Configured',
                        model:
                            $request->model,
                        inputTokens:
                            10,
                        outputTokens:
                            20,
                    );
                }
            };

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,
            request:
                new AiRequest(
                    prompt:
                        'Configuration delivery',
                    estimatedTokens:
                        100,
                ),
            provider:
                'fake',
        );

        $this->assertSame(
            'model-from-config',
            $provider->received->model
        );

        $this->assertSame(
            0.7,
            $provider->received
                ->parameter(
                    'temperature'
                )
        );

        $this->assertSame(
            400,
            $provider->received
                ->parameter(
                    'max_output_tokens'
                )
        );
    }

    public function test_original_request_is_not_mutated(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-immutable'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,
                'model' =>
                    'configured-model',
                'parameters' => [
                    'temperature' =>
                        0.2,
                ],
            ]
        );

        app(
            AiProviderRegistry::class
        )->register(
            $this->provider(
                'fake'
            )
        );

        $request =
            new AiRequest(
                prompt:
                    'Immutable request',
                estimatedTokens:
                    100,
            );

        app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,
            request:
                $request,
            provider:
                'fake',
        );

        $this->assertNull(
            $request->model
        );

        $this->assertSame(
            [],
            $request->parameters
        );
    }

    public function test_usage_guard_still_blocks_before_provider(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-guard'
            );

        $this->aiLimit(
            $plan,
            100
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,
                'model' =>
                    'fake-model',
                'parameters' =>
                    [],
            ]
        );

        $provider =
            new class implements AiProvider {
                public bool $executed =
                    false;

                public function code(): string
                {
                    return 'fake';
                }

                public function execute(
                    AiRequest $request
                ): AiResult {
                    $this->executed =
                        true;

                    return new AiResult(
                        content:
                            'Never',
                        model:
                            'fake-model',
                        inputTokens:
                            1,
                        outputTokens:
                            1,
                    );
                }
            };

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        try {
            app(
                AiConfiguredExecutionService::class
            )->execute(
                tenant:
                    $tenant,
                request:
                    new AiRequest(
                        prompt:
                            'Blocked by quota',
                        estimatedTokens:
                            101,
                    ),
                provider:
                    'fake',
            );

            $this->fail(
                'Expected usage blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );
        }

        $this->assertFalse(
            $provider->executed
        );
    }

    public function test_real_provider_usage_is_recorded_after_configured_execution(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'configured-ai-recording'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,
                'model' =>
                    'configured-model',
                'parameters' =>
                    [],
            ]
        );

        app(
            AiProviderRegistry::class
        )->register(
            new class implements AiProvider {
                public function code(): string
                {
                    return 'fake';
                }

                public function execute(
                    AiRequest $request
                ): AiResult {
                    return new AiResult(
                        content:
                            'Actual usage',
                        model:
                            $request->model,
                        inputTokens:
                            123,
                        outputTokens:
                            77,
                    );
                }
            }
        );

        app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,
            request:
                new AiRequest(
                    prompt:
                        'Record actual usage',
                    estimatedTokens:
                        500,
                ),
            provider:
                'fake',
        );

        $this->assertSame(
            200,
            app(
                TenantUsageService::class
            )->value(
                $tenant,
                UsageMetric::AI_TOKENS
            )
        );

        $record =
            AiUsageRecord::query()
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
            'configured-model',
            $record->model
        );

        $this->assertSame(
            200,
            $record->total_tokens
        );
    }

    public function test_legacy_tenant_remains_compatible(): void
    {
        $tenant =
            $this->tenant(
                'configured-ai-legacy'
            );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,
                'model' =>
                    'legacy-model',
                'parameters' =>
                    [],
            ]
        );

        app(
            AiProviderRegistry::class
        )->register(
            $this->provider(
                'fake'
            )
        );

        $result = app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,
            request:
                new AiRequest(
                    prompt:
                        'Legacy configured execution',
                    estimatedTokens:
                        100,
                ),
            provider:
                'fake',
        );

        $this->assertSame(
            'legacy-model',
            $result->model
        );
    }

    private function provider(
        string $code
    ): AiProvider {
        return new class(
            $code
        ) implements AiProvider {
            public function __construct(
                private readonly string $code
            ) {
            }

            public function code(): string
            {
                return $this->code;
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                return new AiResult(
                    content:
                        'Fake configured result',
                    model:
                        $request->model,
                    inputTokens:
                        10,
                    outputTokens:
                        10,
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