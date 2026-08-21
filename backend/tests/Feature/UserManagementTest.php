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

class UserManagementTest extends TestCase
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

    private function admin(Tenant $tenant): User
    {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Administrador',
            'email' => 'admin@' . $tenant->slug . '.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);
    }

    private function grant(User $user, PermissionEnum $permission): void
    {
        $model = Permission::where('name', $permission->value)->firstOrFail();

        $user->permissions()->syncWithoutDetaching($model->id);
    }

    public function test_users_can_be_listed(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->admin($tenant);

        $this->grant($admin, PermissionEnum::USERS_VIEW);

        app(TenantContext::class)->set($tenant);

        User::create([
            'name' => 'Usu?rio A',
            'email' => 'user-a@tenant-a.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
            'tenant_id' => $tenant->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('http://tenant-a.localhost/users');

        $response->assertOk();
        $response->assertSee('Usu?rio A');
    }

    public function test_user_can_be_created(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->admin($tenant);

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this
    ->actingAs($admin)
    ->withSession([
        '_token' => 'test-token',
    ])
    ->post('http://tenant-a.localhost/users', [
        '_token' => 'test-token',
        'name' => 'Novo Usu?rio',
                'email' => 'novo@tenant-a.local',
                'password' => 'TesteSenha123',
                'password_confirmation' => 'TesteSenha123',
                'role' => 'user',
            ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'novo@tenant-a.local',
            'role' => 'user',
        ]);
    }

    public function test_user_can_be_updated(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->admin($tenant);

        $this->grant($admin, PermissionEnum::USERS_UPDATE);

        app(TenantContext::class)->set($tenant);

        $user = User::create([
            'name' => 'Usu?rio Antigo',
            'email' => 'old@tenant-a.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
            'tenant_id' => $tenant->id,
        ]);

        $response = $this
    ->actingAs($admin)
    ->withSession([
        '_token' => 'test-token',
    ])
    ->put("http://tenant-a.localhost/users/{$user->id}", [
        '_token' => 'test-token',
        'name' => 'Usu?rio Atualizado',
                'email' => 'updated@tenant-a.local',
                'password' => '',
                'password_confirmation' => '',
                'role' => 'user',
            ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Usu?rio Atualizado',
            'email' => 'updated@tenant-a.local',
        ]);
    }

    public function test_user_can_be_deleted(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->admin($tenant);

        $this->grant($admin, PermissionEnum::USERS_DELETE);

        app(TenantContext::class)->set($tenant);

        $user = User::create([
            'name' => 'Usu?rio Remover',
            'email' => 'remove@tenant-a.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
            'tenant_id' => $tenant->id,
        ]);

        $response = $this
    ->actingAs($admin)
    ->withSession([
        '_token' => 'test-token',
    ])
    ->delete("http://tenant-a.localhost/users/{$user->id}", [
        '_token' => 'test-token',
    ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_user_creation_validates_required_fields(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->admin($tenant);

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this
    ->actingAs($admin)
    ->withSession([
        '_token' => 'test-token',
    ])
    ->post('http://tenant-a.localhost/users', [
        '_token' => 'test-token',
    ]);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'role',
        ]);
    }

    public function test_users_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant('tenant-a');
        $tenantB = $this->tenant('tenant-b');

        $adminA = $this->admin($tenantA);
        $this->grant($adminA, PermissionEnum::USERS_VIEW);

        app(TenantContext::class)->set($tenantB);

        User::create([
            'name' => 'Usu?rio Tenant B',
            'email' => 'user@tenant-b.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
            'tenant_id' => $tenantB->id,
        ]);

        $response = $this
            ->actingAs($adminA)
            ->get('http://tenant-a.localhost/users');

        $response->assertOk();
        $response->assertDontSee('Usu?rio Tenant B');
    }

    public function test_user_creation_is_blocked_when_tenant_limit_is_reached(): void
    {
        $tenant = $this->tenant(
            'tenant-limit-reached'
        );

        $admin = $this->admin($tenant);

        $this->grant(
            $admin,
            PermissionEnum::USERS_CREATE
        );

        app(TenantCapabilities::class)->setLimit(
            $tenant,
            Feature::USERS,
            1
        );

        $response = $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->post(
                'http://tenant-limit-reached.localhost/users',
                [
                    '_token' => 'test-token',
                    'name' => 'Usuario Bloqueado',
                    'email' => 'bloqueado@tenant-limit-reached.local',
                    'password' => 'TesteSenha123',
                    'password_confirmation' => 'TesteSenha123',
                    'role' => 'user',
                ]
            );

        $response->assertSessionHasErrors(
            'limit'
        );

        $this->assertDatabaseMissing(
            'users',
            [
                'tenant_id' => $tenant->id,
                'email' => 'bloqueado@tenant-limit-reached.local',
            ]
        );
    }

    public function test_user_creation_is_allowed_below_tenant_limit(): void
    {
        $tenant = $this->tenant(
            'tenant-limit-available'
        );

        $admin = $this->admin($tenant);

        $this->grant(
            $admin,
            PermissionEnum::USERS_CREATE
        );

        app(TenantCapabilities::class)->setLimit(
            $tenant,
            Feature::USERS,
            2
        );

        $response = $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->post(
                'http://tenant-limit-available.localhost/users',
                [
                    '_token' => 'test-token',
                    'name' => 'Usuario Permitido',
                    'email' => 'permitido@tenant-limit-available.local',
                    'password' => 'TesteSenha123',
                    'password_confirmation' => 'TesteSenha123',
                    'role' => 'user',
                ]
            );

        $response->assertRedirect(
            route('users.index')
        );

        $this->assertDatabaseHas(
            'users',
            [
                'tenant_id' => $tenant->id,
                'email' => 'permitido@tenant-limit-available.local',
            ]
        );
    }

    public function test_null_user_limit_keeps_user_creation_unlimited(): void
    {
        $tenant = $this->tenant(
            'tenant-limit-null'
        );

        $admin = $this->admin($tenant);

        $this->grant(
            $admin,
            PermissionEnum::USERS_CREATE
        );

        app(TenantCapabilities::class)->setLimit(
            $tenant,
            Feature::USERS,
            null
        );

        foreach (range(1, 3) as $index) {
            $response = $this
                ->actingAs($admin)
                ->withSession([
                    '_token' => 'test-token',
                ])
                ->post(
                    'http://tenant-limit-null.localhost/users',
                    [
                        '_token' => 'test-token',
                        'name' => "Usuario {$index}",
                        'email' => "usuario{$index}@tenant-limit-null.local",
                        'password' => 'TesteSenha123',
                        'password_confirmation' => 'TesteSenha123',
                        'role' => 'user',
                    ]
                );

            $response->assertRedirect(
                route('users.index')
            );
        }

        $this->assertSame(
            4,
            User::query()
                ->where('tenant_id', $tenant->id)
                ->count()
        );
    }

    public function test_zero_user_limit_blocks_creation(): void
    {
        $tenant = $this->tenant(
            'tenant-limit-zero'
        );

        $admin = $this->admin($tenant);

        $this->grant(
            $admin,
            PermissionEnum::USERS_CREATE
        );

        app(TenantCapabilities::class)->setLimit(
            $tenant,
            Feature::USERS,
            0
        );

        $response = $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->post(
                'http://tenant-limit-zero.localhost/users',
                [
                    '_token' => 'test-token',
                    'name' => 'Usuario Zero',
                    'email' => 'zero@tenant-limit-zero.local',
                    'password' => 'TesteSenha123',
                    'password_confirmation' => 'TesteSenha123',
                    'role' => 'user',
                ]
            );

        $response->assertSessionHasErrors(
            'limit'
        );

        $this->assertDatabaseMissing(
            'users',
            [
                'tenant_id' => $tenant->id,
                'email' => 'zero@tenant-limit-zero.local',
            ]
        );
    }

    public function test_user_limits_are_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'tenant-limit-a'
        );

        $tenantB = $this->tenant(
            'tenant-limit-b'
        );

        $adminA = $this->admin($tenantA);
        $adminB = $this->admin($tenantB);

        $this->grant(
            $adminA,
            PermissionEnum::USERS_CREATE
        );

        $this->grant(
            $adminB,
            PermissionEnum::USERS_CREATE
        );

        app(TenantCapabilities::class)->setLimit(
            $tenantA,
            Feature::USERS,
            1
        );

        app(TenantCapabilities::class)->setLimit(
            $tenantB,
            Feature::USERS,
            2
        );

        $responseA = $this
            ->actingAs($adminA)
            ->withSession([
                '_token' => 'test-token-a',
            ])
            ->post(
                'http://tenant-limit-a.localhost/users',
                [
                    '_token' => 'test-token-a',
                    'name' => 'Bloqueado A',
                    'email' => 'bloqueado@tenant-limit-a.local',
                    'password' => 'TesteSenha123',
                    'password_confirmation' => 'TesteSenha123',
                    'role' => 'user',
                ]
            );

        $responseA->assertSessionHasErrors(
            'limit'
        );

        $responseB = $this
            ->actingAs($adminB)
            ->withSession([
                '_token' => 'test-token-b',
            ])
            ->post(
                'http://tenant-limit-b.localhost/users',
                [
                    '_token' => 'test-token-b',
                    'name' => 'Permitido B',
                    'email' => 'permitido@tenant-limit-b.local',
                    'password' => 'TesteSenha123',
                    'password_confirmation' => 'TesteSenha123',
                    'role' => 'user',
                ]
            );

        $responseB->assertRedirect(
            route('users.index')
        );

        $this->assertDatabaseHas(
            'users',
            [
                'tenant_id' => $tenantB->id,
                'email' => 'permitido@tenant-limit-b.local',
            ]
        );
    }
}
