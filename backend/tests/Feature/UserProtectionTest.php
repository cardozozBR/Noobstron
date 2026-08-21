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

class UserProtectionTest extends TestCase
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
            'name' => $role === 'admin'
                ? 'Administrador'
                : 'Usuário Teste',
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

        $user->permissions()
            ->syncWithoutDetaching($model->id);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant(
            $admin,
            PermissionEnum::USERS_DELETE
        );

        $response = $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->delete(
                "http://tenant-a.localhost/users/{$admin->id}",
                [
                    '_token' => 'test-token',
                ]
            );

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_delete_another_admin(): void
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

        $this->grant(
            $admin,
            PermissionEnum::USERS_DELETE
        );

        $response = $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->delete(
                "http://tenant-a.localhost/users/{$otherAdmin->id}",
                [
                    '_token' => 'test-token',
                ]
            );

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
        ]);
    }

    public function test_admin_cannot_demote_another_admin(): void
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

        $this->grant(
            $admin,
            PermissionEnum::USERS_UPDATE
        );

        $response = $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->put(
                "http://tenant-a.localhost/users/{$otherAdmin->id}",
                [
                    '_token' => 'test-token',
                    'name' => $otherAdmin->name,
                    'email' => $otherAdmin->email,
                    'password' => '',
                    'password_confirmation' => '',
                    'role' => 'user',
                ]
            );

        $response->assertRedirect(
            route('users.edit', $otherAdmin->id)
        );

        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
            'role' => 'admin',
        ]);
    }

    public function test_role_change_creates_audit_log(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $target = $this->user(
            $tenant,
            'usuario@tenant-a.local',
            'user'
        );

        $this->grant(
            $admin,
            PermissionEnum::USERS_UPDATE
        );

        $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->put(
                "http://tenant-a.localhost/users/{$target->id}",
                [
                    '_token' => 'test-token',
                    'name' => $target->name,
                    'email' => $target->email,
                    'password' => '',
                    'password_confirmation' => '',
                    'role' => 'admin',
                ]
            );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => 'user.role.updated',
        ]);
    }

    public function test_role_change_to_admin_syncs_admin_permissions(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $target = $this->user(
            $tenant,
            'usuario@tenant-a.local',
            'user'
        );

        $this->grant(
            $admin,
            PermissionEnum::USERS_UPDATE
        );

        $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->put(
                "http://tenant-a.localhost/users/{$target->id}",
                [
                    '_token' => 'test-token',
                    'name' => $target->name,
                    'email' => $target->email,
                    'password' => '',
                    'password_confirmation' => '',
                    'role' => 'admin',
                ]
            );

        $target->refresh();

        $this->assertTrue(
            $target->hasPermission(
                PermissionEnum::USERS_VIEW
            )
        );

        $this->assertTrue(
            $target->hasPermission(
                PermissionEnum::AUDIT_VIEW
            )
        );
    }
}