<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialIndicatorUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_financial_indicator_page_uses_tenant_locale(): void
    {
        $tenant = $this->tenant(
            'financial-ui-en',
            'en'
        );

        $user = $this->user(
            $tenant,
            'financial-ui-en@local'
        );

        $this->enable(
            $tenant
        );

        $this->grant(
            $user
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant
                )
            )
            ->assertOk()
            ->assertSee(
                'Financial indicators'
            )
            ->assertSee(
                'Track receipts, outstanding amounts, and revenue.'
            )
            ->assertSee(
                'Revenue by customer'
            );
    }

    public function test_navigation_shows_financial_indicators_when_allowed(): void
    {
        $tenant = $this->tenant(
            'financial-ui-nav',
            'pt-BR'
        );

        $user = $this->user(
            $tenant,
            'financial-ui-nav@local'
        );

        $this->enable(
            $tenant
        );

        $this->grant(
            $user
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant
                )
            )
            ->assertOk()
            ->assertSee(
                __('financial_indicators.navigation')
            );
    }

    private function tenant(
        string $slug,
        string $locale
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
                $locale,
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

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' =>
                'Financial UI User',
            'email' =>
                $email,
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' =>
                'user',
        ]);
    }

    private function enable(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::FINANCIAL_INDICATORS,
            true
        );
    }

    private function grant(
        User $user
    ): void {
        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::FINANCIAL_INDICATORS_VIEW->value
            )
            ->firstOrFail();

        $user
            ->permissions()
            ->syncWithoutDetaching([
                $permission->id,
            ]);
    }

    private function url(
        Tenant $tenant
    ): string {
        return 'http://'
            . $tenant->slug
            . '.localhost/financial-indicators';
    }
}