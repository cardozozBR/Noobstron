<?php

namespace Tests\Feature;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function user(
        Tenant $tenant,
        string $role = 'user',
        string $email = 'user@tenant-a.local'
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Usuário Teste',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => $role,
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::where(
            'name',
            $permission->value
        )->firstOrFail();

        $user->permissions()->syncWithoutDetaching(
            $model->id
        );
    }

    public function test_password_is_not_exposed_in_user_array(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant);

        $data = $user->toArray();

        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    public function test_password_is_stored_hashed(): void
    {
        $tenant = $this->tenant('tenant-a');

        app(TenantContext::class)->set($tenant);

        $user = User::create([
            'name' => 'Usuário Teste',
            'email' => 'hash@tenant-a.local',
            'password' => 'SenhaSegura123',
            'role' => 'user',
        ]);

        $hash = $user->getRawOriginal('password');

        $this->assertNotSame('SenhaSegura123', $hash);
        $this->assertTrue(
            Hash::check('SenhaSegura123', $hash)
        );
    }

    public function test_user_cannot_access_another_tenant(): void
    {
        $tenantA = $this->tenant('tenant-a');
        $tenantB = $this->tenant('tenant-b');

        $user = $this->user(
            $tenantA,
            'user',
            'user@tenant-a.local'
        );

        $this->grant(
            $user,
            PermissionEnum::USERS_VIEW
        );

        app(TenantContext::class)->set($tenantB);

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-b.localhost/users');

        $response->assertForbidden();
    }

    public function test_user_without_permission_cannot_access_users(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant);

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-a.localhost/users');

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->tenant('tenant-a');

        $this
            ->get('http://tenant-a.localhost/users')
            ->assertRedirect();

        $this
            ->get('http://tenant-a.localhost/profile')
            ->assertRedirect();

        $this
            ->get('http://tenant-a.localhost/audit')
            ->assertRedirect();
    }
}