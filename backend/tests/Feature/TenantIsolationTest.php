<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_query_only_returns_users_from_current_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenantA);

        $userA = User::create([
            'name' => 'Usuário A',
            'email' => 'usuario@tenant-a.local',
            'password' => 'TesteSenha123',
            'role' => 'user',
        ]);

        app(TenantContext::class)->set($tenantB);

        $userB = User::create([
            'name' => 'Usuário B',
            'email' => 'usuario@tenant-b.local',
            'password' => 'TesteSenha123',
            'role' => 'user',
        ]);

        app(TenantContext::class)->set($tenantA);

        $users = User::query()->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains('id', $userA->id));
        $this->assertFalse($users->contains('id', $userB->id));
    }

    public function test_user_from_another_tenant_cannot_be_found_by_id(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenantB);

        $userB = User::create([
            'name' => 'Usuário B',
            'email' => 'usuario@tenant-b.local',
            'password' => 'TesteSenha123',
            'role' => 'user',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->assertNull(
            User::query()->find($userB->id)
        );
    }
    public function test_user_created_through_controller_belongs_to_current_tenant(): void
{
    $tenant = Tenant::create([
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'status' => 'active',
    ]);

    app(TenantContext::class)->set($tenant);

    app(TenantCapabilities::class)->set(
        $tenant,
        Feature::USERS,
        true
    );

    $admin = User::create([
        'name' => 'Administrador',
        'email' => 'admin@tenant-a.local',
        'password' => 'TesteSenha123',
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

$response = $this
    ->withSession([
        '_token' => 'test-token',
    ])
    ->post(
        'http://tenant-a.localhost/users',
        [
            '_token' => 'test-token',
            'name' => 'Novo Usuário',
            'email' => 'novo@tenant-a.local',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'TesteSenha123',
            'role' => 'user',
        ]
    );

    $response->assertRedirect(route('users.index'));

    $user = User::query()
        ->where('email', 'novo@tenant-a.local')
        ->firstOrFail();

    $this->assertSame(
        $tenant->id,
        $user->tenant_id
    );
}
}
