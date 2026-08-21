<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialIndicatorGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_financial_indicators_feature_exists(): void
    {
        $this->assertSame(
            'financial_indicators',
            Feature::FINANCIAL_INDICATORS->value
        );
    }

    public function test_financial_indicators_permission_exists(): void
    {
        $this->assertDatabaseHas(
            'permissions',
            [
                'name' =>
                    PermissionEnum::FINANCIAL_INDICATORS_VIEW->value,
            ]
        );
    }

    public function test_admin_receives_financial_indicator_permission(): void
    {
        $tenant = $this->tenant(
            'financial-indicator-admin'
        );

        $admin = User::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' =>
                'Financial Admin',
            'email' =>
                'financial-admin@local',
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' =>
                'admin',
        ]);

        app(RolePermissionSync::class)
            ->sync($admin);

        $this->assertTrue(
            $admin
                ->permissions()
                ->where(
                    'name',
                    'financial_indicators.view'
                )
                ->exists()
        );
    }

    public function test_financial_indicator_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'financial-indicator-a'
        );

        $tenantB = $this->tenant(
            'financial-indicator-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenantA,
            Feature::FINANCIAL_INDICATORS,
            true
        );

        $capabilities->set(
            $tenantB,
            Feature::FINANCIAL_INDICATORS,
            false
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenantA,
                Feature::FINANCIAL_INDICATORS
            )
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenantB,
                Feature::FINANCIAL_INDICATORS
            )
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' =>
                'Tenant ' . $slug,
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

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}