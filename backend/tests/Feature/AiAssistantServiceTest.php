<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\Feature;
use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Models\AiUsageRecord;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\AiAssistantService;
use App\Services\AiProviderRegistry;
use App\Services\TenantUsageService;
use App\Support\AiRequest;
use App\Support\AiResult;
use App\Support\TenantCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class AiAssistantServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rewrite_requires_ai_feature(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'assistant-disabled'
        );

        $this->aiLimit(
            $plan,
            1000
        );

        $this->configureProvider();

        $provider = $this->provider();

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        try {
            app(
                AiAssistantService::class
            )->rewrite(
                tenant: $tenant,
                text: 'Original text.',
            );

            $this->fail(
                'Expected AI capability failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'AI feature is not enabled for tenant.',
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

    public function test_rewrite_executes_configured_ai_provider(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'assistant-rewrite'
        );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::AI,
            true
        );

        $this->configureProvider();

        $provider = $this->provider(
            content: 'Improved text.'
        );

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        $result = app(
            AiAssistantService::class
        )->rewrite(
            tenant: $tenant,
            text: ' Original text. ',
            instruction: ' Make it more concise. ',
            estimatedTokens: 300,
        );

        $this->assertSame(
            'Improved text.',
            $result->content
        );

        $this->assertTrue(
            $provider->executed
        );

        $this->assertNotNull(
            $provider->received
        );

        $this->assertSame(
            'assistant-model',
            $provider->received->model
        );

        $this->assertSame(
            300,
            $provider->received->estimatedTokens
        );

        $this->assertStringContainsString(
            'Rewrite the text below.',
            $provider->received->prompt
        );

        $this->assertStringContainsString(
            'Preserve its factual meaning.',
            $provider->received->prompt
        );

        $this->assertStringContainsString(
            'Additional instruction: Make it more concise.',
            $provider->received->prompt
        );

        $this->assertStringContainsString(
            "Text:\nOriginal text.",
            $provider->received->prompt
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
    }

    public function test_rewrite_works_without_optional_instruction(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'assistant-default'
        );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::AI,
            true
        );

        $this->configureProvider();

        $provider = $this->provider();

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        app(
            AiAssistantService::class
        )->rewrite(
            tenant: $tenant,
            text: 'Rewrite this.',
        );

        $this->assertNotNull(
            $provider->received
        );

        $this->assertStringNotContainsString(
            'Additional instruction:',
            $provider->received->prompt
        );

        $this->assertStringContainsString(
            "Text:\nRewrite this.",
            $provider->received->prompt
        );
    }

    public function test_blank_rewrite_text_is_rejected_before_provider(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'assistant-blank'
        );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::AI,
            true
        );

        $this->configureProvider();

        $provider = $this->provider();

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        try {
            app(
                AiAssistantService::class
            )->rewrite(
                tenant: $tenant,
                text: '   ',
            );

            $this->fail(
                'Expected blank text failure.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->assertSame(
                'Text to rewrite is required.',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $provider->executed
        );
    }

    public function test_negative_estimate_is_rejected_before_provider(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'assistant-negative'
        );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::AI,
            true
        );

        $this->configureProvider();

        $provider = $this->provider();

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        try {
            app(
                AiAssistantService::class
            )->rewrite(
                tenant: $tenant,
                text: 'Original.',
                estimatedTokens: -1,
            );

            $this->fail(
                'Expected negative estimate failure.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->assertSame(
                'Estimated AI tokens cannot be negative.',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $provider->executed
        );
    }

    public function test_explicit_provider_is_forwarded(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'assistant-provider'
        );

        $this->aiLimit(
            $plan,
            1000
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::AI,
            true
        );

        config()->set(
            'ai.providers.alternate',
            [
                'enabled' => true,
                'model' => 'alternate-model',
                'parameters' => [],
            ]
        );

        $provider = $this->provider(
            code: 'alternate'
        );

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        $result = app(
            AiAssistantService::class
        )->rewrite(
            tenant: $tenant,
            text: 'Provider test.',
            provider: ' alternate ',
        );

        $this->assertSame(
            'alternate-model',
            $result->model
        );

        $this->assertTrue(
            $provider->executed
        );
    }

    public function test_rewrite_is_isolated_between_tenants(): void
    {
        [$firstTenant, $firstPlan] = $this->subscribedTenant(
            'assistant-first'
        );

        [$secondTenant, $secondPlan] = $this->subscribedTenant(
            'assistant-second'
        );

        $this->aiLimit(
            $firstPlan,
            1000
        );

        $this->aiLimit(
            $secondPlan,
            1000
        );

        app(
            TenantCapabilities::class
        )->set(
            $firstTenant,
            Feature::AI,
            true
        );

        $this->configureProvider();

        $provider = $this->provider();

        app(
            AiProviderRegistry::class
        )->register(
            $provider
        );

        app(
            AiAssistantService::class
        )->rewrite(
            tenant: $firstTenant,
            text: 'Allowed.',
        );

        $this->assertTrue(
            $provider->executed
        );

        $provider->executed = false;

        try {
            app(
                AiAssistantService::class
            )->rewrite(
                tenant: $secondTenant,
                text: 'Blocked.',
            );

            $this->fail(
                'Expected second tenant capability failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'AI feature is not enabled for tenant.',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $provider->executed
        );
    }

    private function configureProvider(): void
    {
        config()->set(
            'ai.default',
            'fake'
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' => true,
                'model' => 'assistant-model',
                'parameters' => [],
            ]
        );
    }

    private function provider(
        string $code = 'fake',
        string $content = 'Rewritten text.'
    ): AiProvider {
        return new class(
            $code,
            $content
        ) implements AiProvider {
            public bool $executed = false;

            public ?AiRequest $received = null;

            public function __construct(
                private readonly string $code,
                private readonly string $content
            ) {
            }

            public function code(): string
            {
                return $this->code;
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                $this->executed = true;
                $this->received = $request;

                return new AiResult(
                    content: $this->content,
                    model: $request->model,
                    inputTokens: 20,
                    outputTokens: 10,
                );
            }
        };
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

        DB::table(
            'subscriptions'
        )->insert([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'current_period_start' => '2026-08-18 00:00:00',
            'current_period_end' => '2026-09-18 00:00:00',
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
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);
    }

    private function aiLimit(
        Plan $plan,
        ?int $limit
    ): void {
        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' => UsageMetric::AI_TOKENS,
            'limit_value' => $limit,
        ]);
    }
}