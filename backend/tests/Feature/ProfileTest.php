<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

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
        string $email = 'usuario@tenant-a.local'
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Usuário Teste',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);
    }

    private function putProfile(
        User $user,
        array $data,
        string $host = 'tenant-a'
    ) {
        return $this
            ->actingAs($user)
            ->withSession([
                '_token' => 'test-token',
            ])
            ->put("http://{$host}.localhost/profile", array_merge([
                '_token' => 'test-token',
            ], $data));
    }

    public function test_authenticated_user_can_view_own_profile(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant);

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-a.localhost/profile');

        $response->assertOk();
        $response->assertSee('Meu Perfil');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_user_can_update_profile_name_and_email(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant);

        $response = $this->putProfile($user, [
            'name' => 'Nome Atualizado',
            'email' => 'atualizado@tenant-a.local',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'tenant_id' => $tenant->id,
            'name' => 'Nome Atualizado',
            'email' => 'atualizado@tenant-a.local',
        ]);
    }

    public function test_user_can_update_password(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant);

        $response = $this->putProfile($user, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ]);

        $response->assertRedirect();

        $user->refresh();

        $this->assertTrue(
            Hash::check('NovaSenha123', $user->password)
        );
    }

    public function test_profile_email_must_be_unique_inside_current_tenant(): void
    {
        $tenant = $this->tenant('tenant-a');

        $user = $this->user(
            $tenant,
            'usuario@tenant-a.local'
        );

        $this->user(
            $tenant,
            'existente@tenant-a.local'
        );

        $response = $this->putProfile($user, [
            'name' => $user->name,
            'email' => 'existente@tenant-a.local',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_same_profile_email_can_exist_in_another_tenant(): void
    {
        $tenantA = $this->tenant('tenant-a');
        $tenantB = $this->tenant('tenant-b');

        $userA = $this->user(
            $tenantA,
            'usuario-a@tenant-a.local'
        );

        $this->user(
            $tenantB,
            'compartilhado@teste.local'
        );

        app(TenantContext::class)->set($tenantA);

        $response = $this->putProfile($userA, [
            'name' => $userA->name,
            'email' => 'compartilhado@teste.local',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $userA->id,
            'tenant_id' => $tenantA->id,
            'email' => 'compartilhado@teste.local',
        ]);
    }

    public function test_profile_update_creates_audit_log(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant);

        $this->putProfile($user, [
            'name' => 'Nome Auditável',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'profile.updated',
        ]);
    }
}