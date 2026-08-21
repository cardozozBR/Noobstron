<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function tenant(string $slug): Tenant
    {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::USERS,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email,
        string $role = 'user'
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => $role === 'admin' ? 'Administrador' : 'Usuário Teste',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => $role,
        ]);
    }

    private function permission(string $name): Permission
    {
        return Permission::where('name', $name)->firstOrFail();
    }

    private function putPermissions(
        User $admin,
        User $target,
        array $permissionIds
    ) {
        return $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->put(
                "http://tenant-a.localhost/users/{$target->id}/permissions",
                [
                    '_token' => 'test-token',
                    'permissions' => $permissionIds,
                ]
            );
    }

    public function test_admin_can_view_user_permissions(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $target = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $response = $this
            ->actingAs($admin)
            ->get(
                "http://tenant-a.localhost/users/{$target->id}/permissions"
            );

        $response->assertOk();
        $response->assertSee('Permissões do usuário');
        $response->assertSee($target->email);
    }

    public function test_admin_can_assign_permissions_to_user(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $target = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $usersView = $this->permission('users.view');
        $auditView = $this->permission('audit.view');

        $response = $this->putPermissions(
            $admin,
            $target,
            [
                $usersView->id,
                $auditView->id,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('permission_user', [
            'user_id' => $target->id,
            'permission_id' => $usersView->id,
        ]);

        $this->assertDatabaseHas('permission_user', [
            'user_id' => $target->id,
            'permission_id' => $auditView->id,
        ]);
    }

    public function test_admin_can_remove_permission_from_user(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $target = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $usersView = $this->permission('users.view');
        $auditView = $this->permission('audit.view');

        $target->permissions()->sync([
            $usersView->id,
            $auditView->id,
        ]);

        $this->putPermissions(
            $admin,
            $target,
            [
                $usersView->id,
            ]
        );

        $this->assertDatabaseHas('permission_user', [
            'user_id' => $target->id,
            'permission_id' => $usersView->id,
        ]);

        $this->assertDatabaseMissing('permission_user', [
            'user_id' => $target->id,
            'permission_id' => $auditView->id,
        ]);
    }

    public function test_permission_update_creates_audit_log(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $target = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $usersView = $this->permission('users.view');

        $this->putPermissions(
            $admin,
            $target,
            [
                $usersView->id,
            ]
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => 'user.permissions.updated',
        ]);
    }

    public function test_user_permissions_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant('tenant-a');
        $tenantB = $this->tenant('tenant-b');

        $adminA = $this->user(
            $tenantA,
            'admin@tenant-a.local',
            'admin'
        );

        $userB = $this->user(
            $tenantB,
            'usuario@tenant-b.local'
        );

        app(TenantContext::class)->set($tenantA);

        $response = $this
            ->actingAs($adminA)
            ->get(
                "http://tenant-a.localhost/users/{$userB->id}/permissions"
            );

        $response->assertNotFound();
    }

    public function test_admin_permissions_cannot_be_edited(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $otherAdmin = $this->user(
            $tenant,
            'outro-admin@tenant-a.local',
            'admin'
        );

        $response = $this
            ->actingAs($admin)
            ->get(
                "http://tenant-a.localhost/users/{$otherAdmin->id}/permissions"
            );

        $response->assertForbidden();
    }
}