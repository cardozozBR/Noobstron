<?php

namespace Tests\Feature;

use App\Models\PlatformAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_login_page_is_public_without_tenant_context(): void
    {
        app(TenantContext::class)->clear();

        $response = $this->get(
            'http://localhost/platform/login'
        );

        $response
            ->assertOk()
            ->assertSee('Administração da plataforma');
    }

    public function test_tenant_admin_cannot_access_platform_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant comum',
            'slug' => 'tenant-comum',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::query()->create([
            'name' => 'Admin do tenant',
            'email' => 'tenant-admin@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://localhost/platform'
            );

        $response->assertRedirect(
            route('platform.login')
        );
    }

    public function test_platform_admin_can_access_global_dashboard_without_tenant_context(): void
    {
        Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a-global',
            'status' => 'active',
        ]);

        Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b-global',
            'status' => 'active',
        ]);

        $admin = PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => true,
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $admin,
                'platform'
            )
            ->get(
                'http://localhost/platform'
            );

        $response
            ->assertOk()
            ->assertSee('Painel global')
            ->assertSee('Tenants')
            ->assertSee('2');
    }

    public function test_platform_login_authenticates_only_active_platform_admin(): void
    {
        PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform-login@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => true,
        ]);

        $response = $this->post(
            'http://localhost/platform/login',
            [
                'email' => 'platform-login@example.test',
                'password' => 'SenhaSegura123',
            ]
        );

        $response->assertRedirect(
            route('platform.dashboard')
        );

        $this->assertAuthenticated(
            'platform'
        );
    }

    public function test_inactive_platform_admin_cannot_login(): void
    {
        PlatformAdmin::query()->create([
            'name' => 'Platform Admin Inativo',
            'email' => 'inactive-platform@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => false,
        ]);

        $response = $this
            ->from(
                'http://localhost/platform/login'
            )
            ->post(
                'http://localhost/platform/login',
                [
                    'email' =>
                        'inactive-platform@example.test',

                    'password' =>
                        'SenhaSegura123',
                ]
            );

        $response
            ->assertRedirect(
                'http://localhost/platform/login'
            )
            ->assertSessionHasErrors(
                'email'
            );

        $this->assertGuest(
            'platform'
        );
    }
}