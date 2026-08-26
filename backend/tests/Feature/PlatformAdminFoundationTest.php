<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;
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

public function test_mutating_platform_routes_use_web_middleware(): void
{
    $platformRoutes = collect(
        Route::getRoutes()->getRoutes()
    )->filter(
        fn ($route) =>
            (
                $route->uri() === 'platform'
                || str_starts_with(
                    $route->uri(),
                    'platform/'
                )
            )
            && array_intersect(
                $route->methods(),
                [
                    'POST',
                    'PUT',
                    'PATCH',
                    'DELETE',
                ]
            ) !== []
    );

    $this->assertNotEmpty(
        $platformRoutes
    );

    foreach ($platformRoutes as $route) {
        $this->assertContains(
            'web',
            $route->gatherMiddleware(),
            "A rota mutável {$route->getName()} ({$route->uri()}) precisa usar o middleware web/CSRF."
        );
    }
}

public function test_platform_routes_are_not_excluded_from_csrf_protection(): void
{
    $bootstrap = file_get_contents(
        base_path('bootstrap/app.php')
    );

    $this->assertIsString(
        $bootstrap
    );

    preg_match(
        '/preventRequestForgery\s*\(\s*except:\s*\[(.*?)\]\s*\)/s',
        $bootstrap,
        $matches
    );

    $this->assertArrayHasKey(
        1,
        $matches,
        'A configuração de exceções CSRF não foi encontrada.'
    );

    $csrfExceptions = $matches[1];

    $this->assertStringNotContainsString(
        "'platform/",
        $csrfExceptions
    );

    $this->assertStringNotContainsString(
        '"platform/',
        $csrfExceptions
    );
}

    public function test_all_platform_routes_except_login_are_protected_by_platform_admin_middleware(): void
{
    $publicRoutes = [
        'platform.login',
        'platform.login.store',
    ];

    $platformRoutes = collect(
        Route::getRoutes()->getRoutes()
    )->filter(
        fn ($route) =>
            $route->uri() === 'platform'
            || str_starts_with(
                $route->uri(),
                'platform/'
            )
    );

    $this->assertNotEmpty(
        $platformRoutes
    );

    foreach ($platformRoutes as $route) {
        $name = $route->getName();

        $middleware = $route->gatherMiddleware();

        if (
            in_array(
                $name,
                $publicRoutes,
                true
            )
        ) {
            $this->assertNotContains(
                'platform.admin',
                $middleware,
                "A rota pública {$name} não deve usar platform.admin."
            );

            continue;
        }

        $this->assertContains(
            'platform.admin',
            $middleware,
            "A rota {$name} ({$route->uri()}) precisa usar platform.admin."
        );
    }
}

public function test_platform_routes_do_not_use_resolve_tenant_middleware(): void
{
    $platformRoutes = collect(
        Route::getRoutes()->getRoutes()
    )->filter(
        fn ($route) =>
            $route->uri() === 'platform'
            || str_starts_with(
                $route->uri(),
                'platform/'
            )
    );

    $this->assertNotEmpty(
        $platformRoutes
    );

    foreach ($platformRoutes as $route) {
        $middleware = $route->gatherMiddleware();

        $this->assertNotContains(
            ResolveTenant::class,
            $middleware,
            "A rota {$route->getName()} ({$route->uri()}) não deve usar ResolveTenant."
        );
    }
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
    public function test_platform_login_is_rate_limited_after_five_failed_attempts(): void
{
    $email = 'rate-limited-platform@example.test';
    $ip = '127.0.0.10';

    PlatformAdmin::query()->create([
        'name' => 'Platform Admin',
        'email' => $email,
        'password' => Hash::make('SenhaCorreta123'),
        'is_active' => true,
    ]);

    $key = implode('|', [
        'platform-login',
        strtolower($email),
        $ip,
    ]);

    RateLimiter::clear($key);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => $ip,
            ])
            ->from('http://localhost/platform/login')
            ->post(
                'http://localhost/platform/login',
                [
                    'email' => $email,
                    'password' => 'SenhaIncorreta123',
                ]
            );

        $response
            ->assertRedirect(
                'http://localhost/platform/login'
            )
            ->assertSessionHasErrors('email');
    }

    $this->assertSame(
        5,
        RateLimiter::attempts($key)
    );

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])
        ->from('http://localhost/platform/login')
        ->post(
            'http://localhost/platform/login',
            [
                'email' => $email,
                'password' => 'SenhaCorreta123',
            ]
        );

    $response
        ->assertRedirect(
            'http://localhost/platform/login'
        )
        ->assertSessionHasErrors('email');

    $this->assertGuest('platform');

    RateLimiter::clear($key);
}

public function test_successful_platform_login_clears_rate_limiter(): void
{
    $email = 'rate-clear-platform@example.test';
    $ip = '127.0.0.11';

    PlatformAdmin::query()->create([
        'name' => 'Platform Admin',
        'email' => $email,
        'password' => Hash::make('SenhaCorreta123'),
        'is_active' => true,
    ]);

    $key = implode('|', [
        'platform-login',
        strtolower($email),
        $ip,
    ]);

    RateLimiter::clear($key);

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])
        ->from('http://localhost/platform/login')
        ->post(
            'http://localhost/platform/login',
            [
                'email' => $email,
                'password' => 'SenhaIncorreta123',
            ]
        );

    $response->assertSessionHasErrors('email');

    $this->assertSame(
        1,
        RateLimiter::attempts($key)
    );

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])
        ->post(
            'http://localhost/platform/login',
            [
                'email' => $email,
                'password' => 'SenhaCorreta123',
            ]
        );

    $response->assertRedirect(
        route('platform.dashboard')
    );

    $this->assertAuthenticated('platform');

    $this->assertSame(
        0,
        RateLimiter::attempts($key)
    );
}

public function test_platform_login_regenerates_session_id(): void
{
    PlatformAdmin::query()->create([
        'name' => 'Platform Admin Sessao',
        'email' => 'platform-session@example.test',
        'password' => Hash::make('SenhaSegura123'),
        'is_active' => true,
    ]);

    $this->get(
        'http://localhost/platform/login'
    );

    $sessionIdBeforeLogin = session()->getId();

    $response = $this->post(
        'http://localhost/platform/login',
        [
            'email' => 'platform-session@example.test',
            'password' => 'SenhaSegura123',
        ]
    );

    $response->assertRedirect(
        route('platform.dashboard')
    );

    $this->assertAuthenticated(
        'platform'
    );

    $sessionIdAfterLogin = session()->getId();

    $this->assertNotSame(
        $sessionIdBeforeLogin,
        $sessionIdAfterLogin,
        'O ID da sessão deve ser regenerado após o login.'
    );
}

public function test_platform_logout_invalidates_authenticated_session(): void
{
    $admin = PlatformAdmin::query()->create([
        'name' => 'Platform Admin Logout',
        'email' => 'platform-logout@example.test',
        'password' => Hash::make('SenhaSegura123'),
        'is_active' => true,
    ]);

    $this->actingAs(
        $admin,
        'platform'
    );

    session()->put(
        'platform-test-value',
        'sensitive-value'
    );

    $sessionIdBeforeLogout = session()->getId();

    $response = $this->post(
        'http://localhost/platform/logout'
    );

    $response->assertRedirect(
        route('platform.login')
    );

    $this->assertGuest(
        'platform'
    );

    $this->assertFalse(
        session()->has('platform-test-value'),
        'Os dados da sessão anterior devem ser removidos no logout.'
    );

    $this->assertNotSame(
        $sessionIdBeforeLogout,
        session()->getId(),
        'O ID da sessão deve mudar após o logout.'
    );
}

public function test_platform_login_does_not_reveal_account_existence(): void
{
    PlatformAdmin::query()->create([
        'name' => 'Platform Admin Enumeração',
        'email' => 'known-platform@example.test',
        'password' => Hash::make('SenhaSegura123'),
        'is_active' => true,
    ]);

    $knownAccount = $this
        ->from('http://localhost/platform/login')
        ->post(
            'http://localhost/platform/login',
            [
                'email' => 'known-platform@example.test',
                'password' => 'SenhaErrada123',
            ]
        );

    $unknownAccount = $this
        ->from('http://localhost/platform/login')
        ->post(
            'http://localhost/platform/login',
            [
                'email' => 'unknown-platform@example.test',
                'password' => 'SenhaErrada123',
            ]
        );

    $knownAccount
        ->assertRedirect(
            'http://localhost/platform/login'
        )
        ->assertSessionHasErrors([
            'email' => 'Credenciais inválidas.',
        ]);

    $unknownAccount
        ->assertRedirect(
            'http://localhost/platform/login'
        )
        ->assertSessionHasErrors([
            'email' => 'Credenciais inválidas.',
        ]);

    $this->assertGuest('platform');
}

}