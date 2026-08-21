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

class InputValidationTest extends TestCase
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

        $user->permissions()
            ->syncWithoutDetaching($model->id);
    }

    private function postUser(
        User $admin,
        array $data
    ) {
        return $this
            ->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->post(
                'http://tenant-a.localhost/users',
                array_merge([
                    '_token' => 'test-token',
                ], $data)
            );
    }

    private function putProfile(
        User $user,
        array $data
    ) {
        return $this
            ->actingAs($user)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->put(
                'http://tenant-a.localhost/profile',
                array_merge([
                    '_token' => 'test-token',
                ], $data)
            );
    }

    public function test_user_name_is_required(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($admin, [
            'name' => '',
            'email' => 'novo@tenant-a.local',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'TesteSenha123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_name_cannot_exceed_255_characters(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($admin, [
            'name' => str_repeat('a', 256),
            'email' => 'novo@tenant-a.local',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'TesteSenha123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_email_must_be_valid(): void
    {
        $tenant = $this->tenant('tenant-a');
        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($admin, [
            'name' => 'Novo Usuário',
            'email' => 'email-invalido',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'TesteSenha123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_email_must_be_unique_inside_tenant(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->user(
            $tenant,
            'existente@tenant-a.local'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($admin, [
            'name' => 'Novo Usuário',
            'email' => 'existente@tenant-a.local',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'TesteSenha123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_same_user_email_can_exist_in_another_tenant(): void
    {
        $tenantA = $this->tenant('tenant-a');
        $tenantB = $this->tenant('tenant-b');

        $adminA = $this->user(
            $tenantA,
            'admin@tenant-a.local',
            'admin'
        );

        $this->user(
            $tenantB,
            'compartilhado@teste.local'
        );

        app(TenantContext::class)->set($tenantA);

        $this->grant($adminA, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($adminA, [
            'name' => 'Novo Usuário',
            'email' => 'compartilhado@teste.local',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'TesteSenha123',
            'role' => 'user',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenantA->id,
            'email' => 'compartilhado@teste.local',
        ]);
    }

    public function test_user_password_must_have_at_least_8_characters(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($admin, [
            'name' => 'Novo Usuário',
            'email' => 'novo@tenant-a.local',
            'password' => '1234567',
            'password_confirmation' => '1234567',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_password_confirmation_must_match(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($admin, [
            'name' => 'Novo Usuário',
            'email' => 'novo@tenant-a.local',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'OutraSenha123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_role_must_be_valid(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $response = $this->postUser($admin, [
            'name' => 'Novo Usuário',
            'email' => 'novo@tenant-a.local',
            'password' => 'TesteSenha123',
            'password_confirmation' => 'TesteSenha123',
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_invalid_user_input_does_not_create_record(): void
    {
        $tenant = $this->tenant('tenant-a');

        $admin = $this->user(
            $tenant,
            'admin@tenant-a.local',
            'admin'
        );

        $this->grant($admin, PermissionEnum::USERS_CREATE);

        $this->postUser($admin, [
            'name' => '',
            'email' => 'invalido',
            'password' => '123',
            'password_confirmation' => '456',
            'role' => 'x',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalido',
        ]);
    }

    public function test_profile_name_is_required(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $response = $this->putProfile($user, [
            'name' => '',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_profile_email_must_be_valid(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $response = $this->putProfile($user, [
            'name' => $user->name,
            'email' => 'invalido',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_profile_password_must_have_at_least_8_characters(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $response = $this->putProfile($user, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_profile_password_confirmation_must_match(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $response = $this->putProfile($user, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'OutraSenha123',
        ]);

        $response->assertSessionHasErrors('password');
    }
}