<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug,
        string $name,
        ?string $color = null
    ): Tenant {
        return Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
            'brand_primary_color' => $color,
        ]);
    }

    private function admin(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set($tenant);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Administrador',
            'email' => $email,
            'password' => 'TesteSenha123',
            'role' => 'admin',
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    public function test_tenant_name_is_used_as_commercial_brand(): void
    {
        $tenant = $this->tenant(
            'brand-a',
            'Empresa Internacional'
        );

        $user = $this->admin(
            $tenant,
            'admin@brand-a.local'
        );

        $response = $this
            ->actingAs($user)
            ->get('http://brand-a.localhost/dashboard');

        $response->assertOk();
        $response->assertSee(
            'Empresa Internacional'
        );
    }

    public function test_custom_primary_color_is_rendered(): void
    {
        $tenant = $this->tenant(
            'brand-color',
            'Tenant Color',
            '#AABBCC'
        );

        $user = $this->admin(
            $tenant,
            'admin@brand-color.local'
        );

        $response = $this
            ->actingAs($user)
            ->get('http://brand-color.localhost/dashboard');

        $response->assertOk();

        $response->assertSee(
            '--tenant-primary-color: #AABBCC',
            false
        );
    }

    public function test_default_primary_color_is_rendered_when_unconfigured(): void
    {
        $tenant = $this->tenant(
            'brand-default',
            'Tenant Default'
        );

        $user = $this->admin(
            $tenant,
            'admin@brand-default.local'
        );

        $response = $this
            ->actingAs($user)
            ->get('http://brand-default.localhost/dashboard');

        $response->assertOk();

        $response->assertSee(
            '--tenant-primary-color: #2563EB',
            false
        );
    }

    public function test_branding_is_independent_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'brand-isolated-a',
            'Marca A',
            '#112233'
        );

        $tenantB = $this->tenant(
            'brand-isolated-b',
            'Marca B',
            '#ABCDEF'
        );

        $this->assertSame(
            '#112233',
            $tenantA->effectiveBrandPrimaryColor()
        );

        $this->assertSame(
            '#ABCDEF',
            $tenantB->effectiveBrandPrimaryColor()
        );

        $this->assertSame(
            'Marca A',
            $tenantA->name
        );

        $this->assertSame(
            'Marca B',
            $tenantB->name
        );
    }
    public function test_login_uses_tenant_primary_color(): void
    {
        $this->tenant(
            'brand-login',
            'Marca Login',
            '#445566'
        );

        $response = $this->get(
            'http://brand-login.localhost/login'
        );

        $response->assertOk();

        $response->assertSee(
            '--tenant-primary-color: #445566',
            false
        );

        $response->assertSee(
            'background: var(--tenant-primary-color)',
            false
        );
    }
    public function test_logo_is_rendered_in_header_when_configured(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $tenant = $this->tenant(
            'brand-logo-header',
            'Marca com Logo'
        );

        $path = 'tenant-branding/'
            . $tenant->id
            . '/logo.png';

        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $path,
            'logo'
        );

        $tenant->logo_path = $path;
        $tenant->save();

        $user = $this->admin(
            $tenant,
            'admin@brand-logo-header.local'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://brand-logo-header.localhost/dashboard'
            );

        $response->assertOk();

        $response->assertSee(
            'storage/' . $path,
            false
        );

        $response->assertSee(
            'alt="Marca com Logo"',
            false
        );
    }

    public function test_commercial_name_is_fallback_when_logo_is_missing(): void
    {
        $tenant = $this->tenant(
            'brand-name-fallback',
            'Marca Sem Logo'
        );

        $user = $this->admin(
            $tenant,
            'admin@brand-name-fallback.local'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://brand-name-fallback.localhost/dashboard'
            );

        $response->assertOk();

        $response->assertSee(
            'Marca Sem Logo',
            false
        );
    }
}