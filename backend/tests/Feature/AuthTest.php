<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function createUser(
        Tenant $tenant,
        string $email = 'usuario@tenant.local',
        string $password = 'TesteSenha123',
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Usuário Teste',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'user',
        ]);
    }

    public function test_login_page_is_accessible_for_guest(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $response = $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $response->assertOk();
        $response->assertSee('Noobstron');
    }

    public function test_valid_login_redirects_to_dashboard(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $this->createUser($tenant);

        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $response = $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                '_token' => csrf_token(),
                'email' => 'usuario@tenant.local',
                'password' => 'TesteSenha123',
            ]
        );

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_valid_login_authenticates_user_from_current_tenant(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $user = $this->createUser($tenant);

        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                '_token' => csrf_token(),
                'email' => 'usuario@tenant.local',
                'password' => 'TesteSenha123',
            ]
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_password_does_not_authenticate_user(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $this->createUser($tenant);

        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $response = $this
            ->from("http://{$tenant->slug}.localhost/login")
            ->post(
                "http://{$tenant->slug}.localhost/login",
                [
                    '_token' => csrf_token(),
                    'email' => 'usuario@tenant.local',
                    'password' => 'senha-incorreta',
                ]
            );

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_from_another_tenant_cannot_login(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');

        $this->get(
            "http://{$tenantA->slug}.localhost/login"
        );

        $this->createUser(
            $tenantB,
            'usuario@tenant-b.local',
            'TesteSenha123'
        );

        $response = $this
            ->from("http://{$tenantA->slug}.localhost/login")
            ->post(
                "http://{$tenantA->slug}.localhost/login",
                [
                    '_token' => csrf_token(),
                    'email' => 'usuario@tenant-b.local',
                    'password' => 'TesteSenha123',
                ]
            );

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_successful_login_creates_audit_log(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $this->createUser($tenant);

        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                '_token' => csrf_token(),
                'email' => 'usuario@tenant.local',
                'password' => 'TesteSenha123',
            ]
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'login.success',
        ]);
    }

    public function test_failed_login_creates_audit_log(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $this->createUser($tenant);

        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                '_token' => csrf_token(),
                'email' => 'usuario@tenant.local',
                'password' => 'senha-incorreta',
            ]
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'login.failed',
        ]);
    }

    public function test_logout_ends_authenticated_session(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $user = $this->createUser($tenant);

        /*
         * Primeiro acessamos a página de login para criar
         * uma sessão válida e obter o token CSRF.
         */
        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $token = csrf_token();

        /*
         * Agora autenticamos o usuário na mesma sessão.
         */
        $this->actingAs($user);

        $this->assertAuthenticatedAs($user);

        /*
         * Enviamos o token CSRF explicitamente.
         */
        $response = $this->post(
            "http://{$tenant->slug}.localhost/logout",
            [
                '_token' => $token,
            ]
        );

        $response->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_logout_creates_audit_log(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $user = $this->createUser($tenant);

        /*
         * Cria a sessão/CSRF antes do POST.
         */
        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        $token = csrf_token();

        $this->actingAs($user);

        $this->post(
            "http://{$tenant->slug}.localhost/logout",
            [
                '_token' => $token,
            ]
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $tenant = $this->createTenant(
            'tenant-rate-limit'
        );

        $email = 'rate-limit@tenant.local';

        $this->createUser(
            $tenant,
            $email
        );

        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        foreach (range(1, 6) as $attempt) {
            $this->from(
                "http://{$tenant->slug}.localhost/login"
            )->post(
                "http://{$tenant->slug}.localhost/login",
                [
                    '_token' => csrf_token(),
                    'email' => $email,
                    'password' => 'senha-incorreta',
                ]
            );
        }

        $this->assertSame(
            5,
            \App\Models\AuditLog::query()
                ->where(
                    'action',
                    'login.failed'
                )
                ->count()
        );

        $this->assertGuest();
    }

    public function test_successful_login_clears_failed_attempt_counter(): void
    {
        $tenant = $this->createTenant(
            'tenant-rate-clear'
        );

        $email = 'rate-clear@tenant.local';

        $user = $this->createUser(
            $tenant,
            $email
        );

        $this->get(
            "http://{$tenant->slug}.localhost/login"
        );

        foreach (range(1, 4) as $attempt) {
            $this->from(
                "http://{$tenant->slug}.localhost/login"
            )->post(
                "http://{$tenant->slug}.localhost/login",
                [
                    '_token' => csrf_token(),
                    'email' => $email,
                    'password' => 'senha-incorreta',
                ]
            );
        }

        $key = implode('|', [
            'login',
            $tenant->id,
            strtolower($email),
            '127.0.0.1',
        ]);

        $this->assertSame(
            4,
            RateLimiter::attempts($key)
        );

        $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                '_token' => csrf_token(),
                'email' => $email,
                'password' => 'TesteSenha123',
            ]
        );

        $this->assertAuthenticatedAs(
            $user
        );

        $this->assertSame(
            0,
            RateLimiter::attempts($key)
        );
    }

    public function test_login_rate_limit_is_isolated_between_tenants(): void
    {
        $tenantA = $this->createTenant(
            'tenant-rate-a'
        );

        $tenantB = $this->createTenant(
            'tenant-rate-b'
        );

        $email = 'same-rate@example.local';

        $this->createUser(
            $tenantA,
            $email
        );

        $this->createUser(
            $tenantB,
            $email
        );

        $this->get(
            "http://{$tenantA->slug}.localhost/login"
        );

        foreach (range(1, 5) as $attempt) {
            $this->from(
                "http://{$tenantA->slug}.localhost/login"
            )->post(
                "http://{$tenantA->slug}.localhost/login",
                [
                    '_token' => csrf_token(),
                    'email' => $email,
                    'password' => 'senha-incorreta',
                ]
            );
        }

        $this->get(
            "http://{$tenantB->slug}.localhost/login"
        );

        $response = $this
            ->from(
                "http://{$tenantB->slug}.localhost/login"
            )
            ->post(
                "http://{$tenantB->slug}.localhost/login",
                [
                    '_token' => csrf_token(),
                    'email' => $email,
                    'password' => 'senha-incorreta',
                ]
            );

        $response->assertSessionHasErrors(
            'email'
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenantB->id,
                'action' => 'login.failed',
            ]
        );
    }
}