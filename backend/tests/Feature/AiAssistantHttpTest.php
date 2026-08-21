<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\Feature;
use App\Enums\SubscriptionStatus;
use App\Enums\Permission as PermissionEnum;
use App\Enums\UsageMetric;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AiProviderRegistry;
use App\Services\TenantContext;
use App\Services\TenantUsageService;
use App\Support\AiRequest;
use App\Support\AiResult;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AiAssistantHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_rewrite_requires_authentication(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'ai-http-auth'
        );

        $this->aiLimit($plan, 1000);
        $this->enableAi($tenant);

        $this
            ->postJson(
                "http://{$tenant->slug}.localhost/ai/rewrite",
                [
                    'text' => 'Rewrite this.',
                ]
            )
            ->assertUnauthorized();
    }

    public function test_rewrite_requires_ai_feature(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'ai-http-feature'
        );

        $this->aiLimit($plan, 1000);

        $user = $this->user(
            $tenant,
            'feature@ai.local'
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AI,
            false
        );

        $this
            ->actingAs($user)
            ->postJson(
                "http://{$tenant->slug}.localhost/ai/rewrite",
                [
                    'text' => 'Rewrite this.',
                ]
            )
            ->assertForbidden();
    }

    public function test_rewrite_validates_required_text(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'ai-http-validation'
        );

        $this->aiLimit($plan, 1000);
        $this->enableAi($tenant);

        $user = $this->user(
            $tenant,
            'validation@ai.local'
        );

        $this->grant(
            $user,
            PermissionEnum::AI_USE
        );

        $this
            ->actingAs($user)
            ->postJson(
                "http://{$tenant->slug}.localhost/ai/rewrite",
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    }

    public function test_rewrite_rejects_negative_estimated_tokens(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'ai-http-negative'
        );

        $this->aiLimit($plan, 1000);
        $this->enableAi($tenant);

        $user = $this->user(
            $tenant,
            'negative@ai.local'
        );

        $this->grant(
            $user,
            PermissionEnum::AI_USE
        );

        $this
            ->actingAs($user)
            ->postJson(
                "http://{$tenant->slug}.localhost/ai/rewrite",
                [
                    'text' => 'Rewrite this.',
                    'estimated_tokens' => -1,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'estimated_tokens'
            );
    }

    public function test_rewrite_requires_ai_permission(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'ai-http-permission'
        );

        $this->aiLimit($plan, 1000);
        $this->enableAi($tenant);

        $user = $this->user(
            $tenant,
            'permission@ai.local'
        );

        $this
            ->actingAs($user)
            ->postJson(
                "http://{$tenant->slug}.localhost/ai/rewrite",
                [
                    'text' => 'Permission test.',
                ]
            )
            ->assertForbidden();
    }

    public function test_authenticated_tenant_can_rewrite_text(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'ai-http-success'
        );

        $this->aiLimit($plan, 1000);
        $this->enableAi($tenant);

        $user = $this->user(
            $tenant,
            'success@ai.local'
        );

        $this->grant(
            $user,
            PermissionEnum::AI_USE
        );

        $this->configureProvider();

        $provider = $this->provider(
            content: 'Improved HTTP text.'
        );

        app(AiProviderRegistry::class)->register(
            $provider
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                "http://{$tenant->slug}.localhost/ai/rewrite",
                [
                    'text' => ' Original HTTP text. ',
                    'instruction' => ' Make it concise. ',
                    'estimated_tokens' => 300,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.content',
                'Improved HTTP text.'
            )
            ->assertJsonPath(
                'data.model',
                'assistant-http-model'
            )
            ->assertJsonPath(
                'data.usage.input_tokens',
                20
            )
            ->assertJsonPath(
                'data.usage.output_tokens',
                10
            )
            ->assertJsonPath(
                'data.usage.total_tokens',
                30
            );

        $this->assertTrue(
            $provider->executed
        );

        $this->assertNotNull(
            $provider->received
        );

        $this->assertSame(
            300,
            $provider->received->estimatedTokens
        );

        $this->assertStringContainsString(
            'Additional instruction: Make it concise.',
            $provider->received->prompt
        );

        $this->assertSame(
            30,
            app(TenantUsageService::class)->value(
                $tenant,
                UsageMetric::AI_TOKENS
            )
        );
    }

    public function test_user_cannot_execute_ai_for_another_tenant(): void
    {
        [$tenantA, $planA] = $this->subscribedTenant(
            'ai-http-tenant-a'
        );

        [$tenantB, $planB] = $this->subscribedTenant(
            'ai-http-tenant-b'
        );

        $this->aiLimit($planA, 1000);
        $this->aiLimit($planB, 1000);

        $this->enableAi($tenantA);
        $this->enableAi($tenantB);

        $userA = $this->user(
            $tenantA,
            'tenant-a@ai.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::AI_USE
        );

        $this->configureProvider();

        $provider = $this->provider();

        app(AiProviderRegistry::class)->register(
            $provider
        );

        $this
            ->actingAs($userA)
            ->postJson(
                "http://{$tenantB->slug}.localhost/ai/rewrite",
                [
                    'text' => 'Cross tenant attempt.',
                ]
            )
            ->assertForbidden();

        $this->assertFalse(
            $provider->executed
        );

        $this->assertSame(
            0,
            app(TenantUsageService::class)->value(
                $tenantB,
                UsageMetric::AI_TOKENS
            )
        );
    }

    public function test_rewrite_uses_default_estimated_tokens(): void
    {
        [$tenant, $plan] = $this->subscribedTenant(
            'ai-http-default-estimate'
        );

        $this->aiLimit($plan, 1000);
        $this->enableAi($tenant);

        $user = $this->user(
            $tenant,
            'default-estimate@ai.local'
        );

        $this->grant(
            $user,
            PermissionEnum::AI_USE
        );

        $this->configureProvider();

        $provider = $this->provider();

        app(AiProviderRegistry::class)->register(
            $provider
        );

        $this
            ->actingAs($user)
            ->postJson(
                "http://{$tenant->slug}.localhost/ai/rewrite",
                [
                    'text' => 'Use default estimate.',
                ]
            )
            ->assertOk();

        $this->assertNotNull(
            $provider->received
        );

        $this->assertSame(
            500,
            $provider->received->estimatedTokens
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
                'model' => 'assistant-http-model',
                'parameters' => [],
            ]
        );
    }

    private function provider(
        string $content = 'Rewritten HTTP text.'
    ): AiProvider {
        return new class(
            $content
        ) implements AiProvider {
            public bool $executed = false;

            public ?AiRequest $received = null;

            public function __construct(
                private readonly string $content
            ) {
            }

            public function code(): string
            {
                return 'fake';
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

        DB::table('subscriptions')->insert([
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
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'AI HTTP User',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where(
                'name',
                $permission->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching(
                $model->id
            );
    }

    private function enableAi(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AI,
            true
        );
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