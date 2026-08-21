<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\AiUsageRecord;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\AiConfiguredExecutionService;
use App\Services\AiProviderRegistry;
use App\Services\OpenAiProvider;
use App\Services\TenantUsageService;
use App\Support\AiRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiConfiguredExecutionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set(
            'ai.default',
            'openai'
        );

        config()->set(
            'ai.providers.openai',
            [
                'enabled' =>
                    true,

                'model' =>
                    'test-openai-model',

                'api_key' =>
                    'test-openai-key',

                'base_url' =>
                    'https://api.openai.com/v1',

                'timeout' =>
                    30,

                'parameters' => [
                    'max_output_tokens' =>
                        250,
                ],
            ]
        );
    }

    public function test_configured_execution_runs_openai_and_records_real_usage(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'openai-e2e-success'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response([
                    'id' =>
                        'resp_e2e_success',

                    'model' =>
                        'test-openai-model',

                    'output' => [
                        [
                            'type' =>
                                'message',

                            'role' =>
                                'assistant',

                            'content' => [
                                [
                                    'type' =>
                                        'output_text',

                                    'text' =>
                                        'OpenAI integration works.',
                                ],
                            ],
                        ],
                    ],

                    'usage' => [
                        'input_tokens' =>
                            120,

                        'output_tokens' =>
                            80,

                        'total_tokens' =>
                            200,
                    ],
                ], 200),
        ]);

        $result = app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,

            request:
                new AiRequest(
                    prompt:
                        'Test configured OpenAI integration.',
                    estimatedTokens:
                        500,
                ),
        );

        $this->assertSame(
            'OpenAI integration works.',
            $result->content
        );

        $this->assertSame(
            'test-openai-model',
            $result->model
        );

        $this->assertSame(
            120,
            $result->inputTokens
        );

        $this->assertSame(
            80,
            $result->outputTokens
        );

        $this->assertSame(
            200,
            $result->totalTokens
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
            'openai',
            $record->provider
        );

        $this->assertSame(
            'test-openai-model',
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

        Http::assertSent(
            function (
                Request $request
            ): bool {
                return
                    $request->url()
                        ===
                    'https://api.openai.com/v1/responses'
                    &&
                    $request->hasHeader(
                        'Authorization',
                        'Bearer test-openai-key'
                    )
                    &&
                    $request[
                        'model'
                    ] ===
                    'test-openai-model'
                    &&
                    $request[
                        'input'
                    ] ===
                    'Test configured OpenAI integration.'
                    &&
                    $request[
                        'max_output_tokens'
                    ] ===
                    250;
            }
        );
    }

    public function test_default_provider_resolves_registered_openai_adapter(): void
    {
        $provider = app(
            AiProviderRegistry::class
        )->resolve(
            'openai'
        );

        $this->assertInstanceOf(
            OpenAiProvider::class,
            $provider
        );

        $this->assertSame(
            'openai',
            $provider->code()
        );
    }

    public function test_quota_block_happens_before_openai_http_request(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'openai-e2e-quota'
            );

        $this->aiLimit(
            $plan,
            100
        );

        Http::fake();

        try {
            app(
                AiConfiguredExecutionService::class
            )->execute(
                tenant:
                    $tenant,

                request:
                    new AiRequest(
                        prompt:
                            'This must never reach OpenAI.',
                        estimatedTokens:
                            101,
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

            $this->assertSame(
                101,
                $exception->requested
            );
        }

        Http::assertNothingSent();

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

    public function test_disabled_openai_provider_is_blocked_before_http(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'openai-e2e-disabled'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.providers.openai.enabled',
            false
        );

        Http::fake();

        try {
            app(
                AiConfiguredExecutionService::class
            )->execute(
                tenant:
                    $tenant,

                request:
                    new AiRequest(
                        prompt:
                            'Disabled provider.',
                        estimatedTokens:
                            100,
                    ),
            );

            $this->fail(
                'Expected disabled provider failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'AI provider [openai] is disabled.',
                $exception->getMessage()
            );
        }

        Http::assertNothingSent();

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

    public function test_missing_openai_key_fails_before_http_and_records_nothing(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'openai-e2e-no-key'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        config()->set(
            'ai.providers.openai.api_key',
            null
        );

        Http::fake();

        try {
            app(
                AiConfiguredExecutionService::class
            )->execute(
                tenant:
                    $tenant,

                request:
                    new AiRequest(
                        prompt:
                            'Missing key.',
                        estimatedTokens:
                            100,
                    ),
            );

            $this->fail(
                'Expected API key failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'OpenAI API key is not configured.',
                $exception->getMessage()
            );
        }

        Http::assertNothingSent();

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

    public function test_openai_http_failure_records_no_ai_usage(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'openai-e2e-http-failure'
            );

        $this->aiLimit(
            $plan,
            1000
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response([
                    'error' => [
                        'message' =>
                            'Simulated OpenAI failure.',
                    ],
                ], 500),
        ]);

        try {
            app(
                AiConfiguredExecutionService::class
            )->execute(
                tenant:
                    $tenant,

                request:
                    new AiRequest(
                        prompt:
                            'Provider failure.',
                        estimatedTokens:
                            100,
                    ),
            );

            $this->fail(
                'Expected HTTP failure.'
            );
        } catch (
            RequestException $exception
        ) {
            $this->assertSame(
                500,
                $exception->response->status()
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

    public function test_real_usage_not_estimate_is_persisted(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'openai-e2e-real-usage'
            );

        $this->aiLimit(
            $plan,
            10000
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response([
                    'model' =>
                        'test-openai-model',

                    'output' => [
                        [
                            'type' =>
                                'message',

                            'content' => [
                                [
                                    'type' =>
                                        'output_text',

                                    'text' =>
                                        'Small actual usage.',
                                ],
                            ],
                        ],
                    ],

                    'usage' => [
                        'input_tokens' =>
                            20,

                        'output_tokens' =>
                            10,

                        'total_tokens' =>
                            30,
                    ],
                ], 200),
        ]);

        app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $tenant,

            request:
                new AiRequest(
                    prompt:
                        'Estimated much larger than real.',
                    estimatedTokens:
                        5000,
                ),
        );

        $this->assertSame(
            30,
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
            30,
            $record->total_tokens
        );
    }

    public function test_openai_usage_is_isolated_between_tenants(): void
    {
        [$firstTenant, $firstPlan] =
            $this->subscribedTenant(
                'openai-e2e-first'
            );

        [$secondTenant, $secondPlan] =
            $this->subscribedTenant(
                'openai-e2e-second'
            );

        $this->aiLimit(
            $firstPlan,
            1000
        );

        $this->aiLimit(
            $secondPlan,
            50
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response([
                    'model' =>
                        'test-openai-model',

                    'output' => [
                        [
                            'type' =>
                                'message',

                            'content' => [
                                [
                                    'type' =>
                                        'output_text',

                                    'text' =>
                                        'First tenant result.',
                                ],
                            ],
                        ],
                    ],

                    'usage' => [
                        'input_tokens' =>
                            40,

                        'output_tokens' =>
                            20,

                        'total_tokens' =>
                            60,
                    ],
                ], 200),
        ]);

        app(
            AiConfiguredExecutionService::class
        )->execute(
            tenant:
                $firstTenant,

            request:
                new AiRequest(
                    prompt:
                        'First tenant.',
                    estimatedTokens:
                        100,
                ),
        );

        try {
            app(
                AiConfiguredExecutionService::class
            )->execute(
                tenant:
                    $secondTenant,

                request:
                    new AiRequest(
                        prompt:
                            'Second tenant.',
                        estimatedTokens:
                            51,
                    ),
            );

            $this->fail(
                'Expected second tenant quota blocking.'
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
            60,
            app(
                TenantUsageService::class
            )->value(
                $firstTenant,
                UsageMetric::AI_TOKENS
            )
        );

        $this->assertSame(
            0,
            app(
                TenantUsageService::class
            )->value(
                $secondTenant,
                UsageMetric::AI_TOKENS
            )
        );

        Http::assertSentCount(
            1
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
}