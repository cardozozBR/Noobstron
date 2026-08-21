<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Support\TenantCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFeatureGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_feature_exists(): void
    {
        $this->assertSame(
            'ai',
            Feature::AI->value
        );

        $this->assertSame(
            'Inteligência Artificial',
            Feature::AI->label()
        );
    }

    public function test_ai_feature_is_disabled_by_default_for_tenant(): void
    {
        $tenant = $this->tenant(
            'ai-disabled-default'
        );

        $this->assertFalse(
            app(
                TenantCapabilities::class
            )->enabled(
                $tenant,
                Feature::AI
            )
        );
    }

    public function test_ai_feature_can_be_enabled_for_tenant(): void
    {
        $tenant = $this->tenant(
            'ai-enabled'
        );

        app(
            TenantCapabilities::class
        )->configure(
            $tenant,
            Feature::AI,
            true
        );

        $this->assertTrue(
            app(
                TenantCapabilities::class
            )->enabled(
                $tenant,
                Feature::AI
            )
        );
    }

    public function test_ai_feature_can_be_disabled_again(): void
    {
        $tenant = $this->tenant(
            'ai-disabled-again'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->configure(
            $tenant,
            Feature::AI,
            true
        );

        $capabilities->configure(
            $tenant,
            Feature::AI,
            false
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenant,
                Feature::AI
            )
        );
    }

    public function test_ai_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'ai-feature-a'
        );

        $tenantB = $this->tenant(
            'ai-feature-b'
        );

        app(
            TenantCapabilities::class
        )->configure(
            $tenantA,
            Feature::AI,
            true
        );

        $this->assertTrue(
            app(
                TenantCapabilities::class
            )->enabled(
                $tenantA,
                Feature::AI
            )
        );

        $this->assertFalse(
            app(
                TenantCapabilities::class
            )->enabled(
                $tenantB,
                Feature::AI
            )
        );
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
}