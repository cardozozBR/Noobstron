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


public function test_authenticated_platform_pages_share_admin_master_navigation(): void
{
    $admin = PlatformAdmin::query()->create([
        'name' => 'Navigation Admin',
        'email' => 'navigation-admin@example.test',
        'password' => Hash::make('SenhaSegura123'),
        'is_active' => true,
    ]);

    $routes = [
        'platform.dashboard',
        'platform.tenants.index',
        'platform.contacts.index',
        'platform.health',
        'platform.jobs',
        'platform.webhooks',
        'platform.email-failures',
        'platform.whatsapp-failures',
    ];

    foreach ($routes as $routeName) {
        $response = $this
            ->actingAs($admin, 'platform')
            ->get(route($routeName));

        $response
            ->assertOk()
            ->assertSee(
                __('platform.nav.aria_label')
            )
            ->assertSee(
                __('platform.nav.dashboard')
            )
            ->assertSee(
                __('platform.nav.tenants')
            )
            ->assertSee(
                __('platform.nav.contacts')
            )
            ->assertSee(
                __('platform.nav.health')
            )
            ->assertSee(
                __('platform.nav.jobs')
            )
            ->assertSee(
                __('platform.nav.webhooks')
            )
            ->assertSee(
                __('platform.nav.email_failures')
            )
            ->assertSee(
                __('platform.nav.whatsapp_failures')
            )
            ->assertSee(
                'platform-navigation__link is-active',
                false
            );
    }
}

public function test_platform_authenticated_pages_show_breadcrumb_navigation(): void
{
    $admin = PlatformAdmin::query()->create([
        'name' => 'Platform Breadcrumb Admin',
        'email' => 'platform-breadcrumb@example.test',
        'password' => Hash::make('SenhaSegura123'),
        'is_active' => true,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Tenant Breadcrumb',
        'slug' => 'tenant-breadcrumb',
        'status' => 'active',
    ]);

    $this
        ->actingAs($admin, 'platform')
        ->get(route('platform.dashboard'))
        ->assertOk()
        ->assertSee(
            __('platform.breadcrumbs.aria_label')
        )
        ->assertSee(
            __('platform.nav.dashboard')
        );

    $this
        ->actingAs($admin, 'platform')
        ->get(route('platform.tenants.index'))
        ->assertOk()
        ->assertSee(
            __('platform.breadcrumbs.aria_label')
        )
        ->assertSee(
            __('platform.nav.dashboard')
        )
        ->assertSee(
            __('platform.nav.tenants')
        );

    $this
        ->actingAs($admin, 'platform')
        ->get(
            route(
                'platform.tenants.show',
                $tenant
            )
        )
        ->assertOk()
        ->assertSee(
            __('platform.breadcrumbs.aria_label')
        )
        ->assertSee(
            __('platform.nav.dashboard')
        )
        ->assertSee(
            __('platform.nav.tenants')
        )
        ->assertSee(
            $tenant->name
        );
}
    public function test_platform_dashboard_uses_links_only_for_actionable_metric_cards(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/platform/dashboard.blade.php'
            )
        );

        $this->assertNotFalse($view);

        $this->assertStringContainsString(
            "route('platform.tenants.index')",
            $view
        );

        $this->assertStringContainsString(
            "'platform.webhooks'",
            $view
        );

        $this->assertStringContainsString(
            "['status' => 'failed']",
            $view
        );

        $this->assertStringContainsString(
            "['status' => 'processing']",
            $view
        );

        $this->assertStringContainsString(
            "route('platform.jobs')",
            $view
        );

        $this->assertStringContainsString(
            "route('platform.email-failures')",
            $view
        );

        $this->assertStringContainsString(
            "route('platform.whatsapp-failures')",
            $view
        );

        $this->assertStringContainsString(
            '.platform-dashboard-page a.metric-card:hover',
            $view
        );

        $this->assertStringContainsString(
            '.platform-dashboard-page a.metric-card:focus-visible',
            $view
        );

        $this->assertStringContainsString(
            'cursor: pointer;',
            $view
        );
    }

    public function test_platform_tables_use_responsive_table_structure(): void
{
    $viewsPath = resource_path('views/platform');

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
            $viewsPath,
            \FilesystemIterator::SKIP_DOTS
        )
    );

    $tableCount = 0;

    foreach ($iterator as $file) {
        if (
            ! $file->isFile()
            || $file->getExtension() !== 'php'
            || ! str_ends_with(
                $file->getFilename(),
                '.blade.php'
            )
        ) {
            continue;
        }

        $contents = file_get_contents(
            $file->getPathname()
        );

        preg_match_all(
            '/<table\b[^>]*>/i',
            $contents,
            $tables
        );

        foreach ($tables[0] as $table) {
            $tableCount++;

            $this->assertMatchesRegularExpression(
                '/class="[^"]*\bplatform-table\b[^"]*"/i',
                $table,
                'Tabela sem platform-table em '
                    .$file->getPathname()
            );
        }
    }

    $this->assertSame(
        14,
        $tableCount,
        'Quantidade inesperada de tabelas Platform.'
    );
}

public function test_platform_paginations_use_consistent_structure(): void
{
    $views = [
        resource_path(
            'views/platform/email-failures.blade.php'
        ),
        resource_path(
            'views/platform/jobs.blade.php'
        ),
        resource_path(
            'views/platform/webhooks.blade.php'
        ),
        resource_path(
            'views/platform/whatsapp-failures.blade.php'
        ),
        resource_path(
            'views/platform/contacts/index.blade.php'
        ),
        resource_path(
            'views/platform/tenants/index.blade.php'
        ),
    ];

    $linksCount = 0;

    foreach ($views as $view) {
        $contents = file_get_contents($view);

        preg_match_all(
            '/->links\s*\(\s*\)/',
            $contents,
            $links
        );

        $linksCount += count(
            $links[0]
        );

        $this->assertSame(
            count($links[0]),
            preg_match_all(
                '/->hasPages\s*\(\s*\)/',
                $contents
            ),
            'Paginação sem hasPages em '.$view
        );

        $this->assertSame(
            count($links[0]),
            preg_match_all(
                '/class="pagination-wrap"/',
                $contents
            ),
            'Paginação sem pagination-wrap em '.$view
        );
    }

    $this->assertSame(
        7,
        $linksCount,
        'Quantidade inesperada de paginações Platform.'
    );
}

public function test_platform_sensitive_actions_require_confirmation(): void
{
    $views = [
        resource_path(
            'views/platform/email-failures.blade.php'
        ) => 1,

        resource_path(
            'views/platform/jobs.blade.php'
        ) => 2,

        resource_path(
            'views/platform/webhooks.blade.php'
        ) => 1,

        resource_path(
            'views/platform/whatsapp-failures.blade.php'
        ) => 1,

        resource_path(
            'views/platform/contacts/index.blade.php'
        ) => 1,

        resource_path(
            'views/platform/tenants/show.blade.php'
        ) => 5,
    ];

    $confirmationCount = 0;

    foreach ($views as $view => $expectedCount) {
        $contents = file_get_contents($view);

        $actualCount = preg_match_all(
            '/onclick="return confirm\s*\(/',
            $contents
        );

        $this->assertSame(
            $expectedCount,
            $actualCount,
            'Quantidade inesperada de confirmações em '.$view
        );

        $confirmationCount += $actualCount;
    }

    $this->assertSame(
        11,
        $confirmationCount,
        'Quantidade inesperada de confirmações sensíveis Platform.'
    );
}


    public function test_platform_flash_messages_use_consistent_structure(): void
    {
        $component = file_get_contents(
            resource_path('views/components/platform/flash.blade.php')
        );

        $this->assertStringContainsString(
            "'platform-flash'",
            $component
        );

        $this->assertStringContainsString(
            "'platform-flash--'",
            $component
        );

        $this->assertStringContainsString(
            "'success'",
            $component
        );

        $this->assertStringContainsString(
            "'error'",
            $component
        );

        $views = [
            'platform/email-failures.blade.php',
            'platform/jobs.blade.php',
            'platform/webhooks.blade.php',
            'platform/whatsapp-failures.blade.php',
        ];

        foreach ($views as $view) {
            $contents = file_get_contents(
                resource_path('views/'.$view)
            );

            $this->assertStringContainsString(
                '<x-platform.flash',
                $contents,
                $view
            );

            $this->assertStringContainsString(
                "session('success')",
                $contents,
                $view
            );

            $this->assertStringContainsString(
                "session('error')",
                $contents,
                $view
            );
        }
    }

    public function test_platform_pages_provide_basic_accessibility(): void
    {
        $layout = file_get_contents(
            resource_path('views/platform/layout.blade.php')
        );

        $this->assertStringContainsString(
            '.button:focus-visible',
            $layout
        );

        $views = [
            'platform/email-failures.blade.php',
            'platform/jobs.blade.php',
            'platform/webhooks.blade.php',
            'platform/whatsapp-failures.blade.php',
            'platform/contacts/index.blade.php',
            'platform/tenants/index.blade.php',
            'platform/tenants/show.blade.php',
        ];

        foreach ($views as $view) {
            $contents = file_get_contents(
                resource_path('views/'.$view)
            );

            preg_match_all(
                '/<th\b[^>]*>/i',
                $contents,
                $matches
            );

            foreach ($matches[0] as $heading) {
                $this->assertStringContainsString(
                    'scope="col"',
                    $heading,
                    "Table heading without scope in {$view}"
                );
            }
        }
    }

    public function test_platform_layout_provides_mobile_baseline(): void
    {
        $layout = file_get_contents(
            resource_path('views/platform/layout.blade.php')
        );

        $this->assertStringContainsString(
            '/* Platform mobile baseline */',
            $layout
        );

        $this->assertStringContainsString(
            'min-height: 44px',
            $layout
        );

        $this->assertStringContainsString(
            '-webkit-overflow-scrolling: touch',
            $layout
        );
    }}
