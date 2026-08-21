<?php

namespace Tests\Feature;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    private function tenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Tenant Teste',
            'slug' => 'tenant-teste',
            'status' => 'active',
        ]);
    }

    private function user(Tenant $tenant, Role $role): User
    {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Usu?rio Teste',
            'email' => $role->value . '@tenant-teste.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => $role,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_admin_role_receives_all_permissions(): void
    {
        $tenant = $this->tenant();
        $user = $this->user($tenant, Role::ADMIN);

        app(RolePermissionSync::class)->sync($user);

        $permissions = $user->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $expected = collect(PermissionEnum::cases())
            ->map(fn (PermissionEnum $permission) => $permission->value)
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expected, $permissions);
    }

    public function test_user_role_receives_only_profile_permissions(): void
    {
        $tenant = $this->tenant();
        $user = $this->user($tenant, Role::USER);

        app(RolePermissionSync::class)->sync($user);

        $permissions = $user->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'profile.update',
            'profile.view',
        ], $permissions);
    }

    public function test_role_change_from_user_to_admin_syncs_permissions(): void
    {
        $tenant = $this->tenant();
        $user = $this->user($tenant, Role::USER);

        app(RolePermissionSync::class)->sync($user);

        $user->role = Role::ADMIN;
        $user->save();

        app(RolePermissionSync::class)->sync($user);

        $freshUser = $user->fresh();

        $this->assertTrue(
            $freshUser->hasPermission(PermissionEnum::USERS_VIEW)
        );

        $this->assertTrue(
            $freshUser->hasPermission(PermissionEnum::AUDIT_VIEW)
        );
    }

    public function test_role_change_from_admin_to_user_removes_admin_permissions(): void
    {
        $tenant = $this->tenant();
        $user = $this->user($tenant, Role::ADMIN);

        app(RolePermissionSync::class)->sync($user);

        $user->role = Role::USER;
        $user->save();

        app(RolePermissionSync::class)->sync($user);

        $freshUser = $user->fresh();

        $this->assertFalse(
            $freshUser->hasPermission(PermissionEnum::USERS_VIEW)
        );

        $this->assertFalse(
            $freshUser->hasPermission(PermissionEnum::AUDIT_VIEW)
        );

        $this->assertTrue(
            $freshUser->hasPermission(PermissionEnum::PROFILE_VIEW)
        );
    }
}
