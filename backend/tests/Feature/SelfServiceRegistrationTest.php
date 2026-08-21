<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TrialService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\TenantContext;
use Tests\TestCase;
use RuntimeException;

class SelfServiceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_public(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('Criar sua conta');
    }

    public function test_registration_page_collects_account_credentials(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('Nome')
            ->assertSee('E-mail')
            ->assertSee('Senha')
            ->assertSee('Confirmar senha')
            ->assertSee(
                'name="name"',
                false
            )
            ->assertSee(
                'name="email"',
                false
            )
            ->assertSee(
                'name="password"',
                false
            )
            ->assertSee(
                'name="password_confirmation"',
                false
            );
    }

    public function test_registration_page_does_not_require_tenant_hostname(): void
    {
        $routes = file_get_contents(
            base_path('routes/web.php')
        );

        $this->assertStringContainsString(
            "Route::get('/register'",
            $routes
        );

        $this->assertStringContainsString(
            "->name('register')",
            $routes
        );
    }

    public function test_existing_login_routes_remain_available(): void
    {
        $routes = file_get_contents(
            base_path('routes/web.php')
        );

        $this->assertStringContainsString(
            "Route::get('/login'",
            $routes
        );

        $this->assertStringContainsString(
            "->name('login')",
            $routes
        );

        $this->assertStringContainsString(
            "Route::post('/login'",
            $routes
        );
    }

    public function test_registration_page_allows_country_selection(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('País')
            ->assertSee(
                'name="country_code"',
                false
            )
            ->assertSee(
                'value="BR"',
                false
            )
            ->assertSee(
                'value="US"',
                false
            )
            ->assertSee('Brasil')
            ->assertSee('United States');
    }

    public function test_registration_page_allows_language_selection(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('Idioma')
            ->assertSee(
                'name="locale"',
                false
            )
            ->assertSee('Português (Brasil)')
            ->assertSee('English')
            ->assertSee('Español')
            ->assertSee('日本語')
            ->assertSee('简体中文');
    }

    public function test_registration_page_allows_plan_selection(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('Plano')
            ->assertSee(
                'name="plan_code"',
                false
            )
            ->assertSee(
                'value="start"',
                false
            )
            ->assertSee('Start')
            ->assertSee(
                'value="pro"',
                false
            )
            ->assertSee('Pro')
            ->assertSee(
                'value="business"',
                false
            )
            ->assertSee('Business')
            ->assertDontSee(
                'value="enterprise"',
                false
            );
    }

    public function test_registration_page_presents_trial(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('14 dias')
            ->assertSee('trial');
    }

    public function test_registration_page_collects_company_name(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('Empresa')
            ->assertSee(
                'name="company_name"',
                false
            );
    }

    public function test_registration_creates_tenant_admin_trial_and_subscription(): void
    {
        Notification::fake();

        $this->seed([
            PermissionSeeder::class,
            PlanCatalogSeeder::class,
        ]);

        $startPlan = Plan::query()
            ->where('code', 'start')
            ->firstOrFail();

        $moment = \Carbon\CarbonImmutable::parse(
            '2026-08-18 12:00:00',
            'UTC'
        );

        \Carbon\CarbonImmutable::setTestNow(
            $moment
        );

        config([
            'app.url' => 'https://platform.example.test:8443',
        ]);

        $response = $this->post('/register', [
            'company_name' => 'Acme Ltda',
            'name' => 'Maria Admin',
            'email' => 'maria@acme.test',
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'plan_code' => 'start',
        ]);

        \Carbon\CarbonImmutable::setTestNow();

        $response->assertRedirect(
            'https://acme-ltda.platform.example.test:8443/login'
        );

        $tenant = Tenant::query()
            ->where('slug', 'acme-ltda')
            ->first();

        $this->assertNotNull($tenant);

        $this->assertSame(
            'Acme Ltda',
            $tenant->name
        );

        $this->assertSame(
            'BR',
            $tenant->country_code
        );

        $this->assertSame(
            'pt-BR',
            $tenant->locale
        );

        $this->assertNotNull(
            $tenant->trial_started_at
        );

        $this->assertNotNull(
            $tenant->trial_ends_at
        );

        $this->assertTrue(
            $tenant->trial_started_at
                ->equalTo($moment)
        );

        $this->assertTrue(
            $tenant->trial_ends_at
                ->equalTo(
                    $moment->addDays(14)
                )
        );

        $user = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', 'maria@acme.test')
            ->first();

        $this->assertNotNull($user);

        $this->assertSame(
            'Maria Admin',
            $user->name
        );

        $this->assertSame(
            'admin',
            $user->role->value
        );

        $this->assertGreaterThan(
            0,
            $user->permissions()->count()
        );

        $this->assertDatabaseHas(
            'subscriptions',
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $startPlan->id,
                'status' => 'active',
            ]
        );
    }

    public function test_registration_rejects_existing_tenant_slug(): void
    {
        Plan::query()->create([
            'code' => 'start',
            'name' => 'Start',
            'active' => true,
        ]);

        Tenant::query()->create([
            'name' => 'Empresa Existente',
            'slug' => 'empresa-existente',
            'status' => 'active',
        ]);

        $response = $this
            ->from('/register')
            ->post('/register', [
                'company_name' => 'Empresa Existente',
                'name' => 'Novo Admin',
                'email' => 'novo@example.test',
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
                'country_code' => 'BR',
                'locale' => 'pt-BR',
                'plan_code' => 'start',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('company_name');

        $this->assertDatabaseCount(
            'tenants',
            1
        );

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'novo@example.test',
            ]
        );
    }

    public function test_registration_rejects_invalid_country(): void
    {
        $response = $this
            ->from('/register')
            ->post('/register', [
                'company_name' => 'Invalid Country',
                'name' => 'Admin',
                'email' => 'country@example.test',
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
                'country_code' => 'XX',
                'locale' => 'pt-BR',
                'plan_code' => 'start',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('country_code');

        $this->assertDatabaseMissing(
            'tenants',
            [
                'slug' => 'invalid-country',
            ]
        );
    }

    public function test_registration_rejects_invalid_locale(): void
    {
        $response = $this
            ->from('/register')
            ->post('/register', [
                'company_name' => 'Invalid Locale',
                'name' => 'Admin',
                'email' => 'locale@example.test',
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
                'country_code' => 'BR',
                'locale' => 'xx-XX',
                'plan_code' => 'start',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('locale');

        $this->assertDatabaseMissing(
            'tenants',
            [
                'slug' => 'invalid-locale',
            ]
        );
    }

    public function test_registration_rejects_enterprise_plan(): void
    {
        $response = $this
            ->from('/register')
            ->post('/register', [
                'company_name' => 'Enterprise Attempt',
                'name' => 'Admin',
                'email' => 'enterprise@example.test',
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
                'country_code' => 'BR',
                'locale' => 'pt-BR',
                'plan_code' => 'enterprise',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('plan_code');

        $this->assertDatabaseMissing(
            'tenants',
            [
                'slug' => 'enterprise-attempt',
            ]
        );
    }

    public function test_registration_rejects_inactive_self_service_plan(): void
    {
        $this->seed(
            \Database\Seeders\PermissionSeeder::class
        );

        Plan::query()->create([
            'code' => 'start',
            'name' => 'Start',
            'active' => false,
        ]);

        $response = $this
            ->from('/register')
            ->post('/register', [
                'company_name' => 'Inactive Plan',
                'name' => 'Admin',
                'email' => 'inactive@example.test',
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
                'country_code' => 'BR',
                'locale' => 'pt-BR',
                'plan_code' => 'start',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('plan_code');

        $this->assertDatabaseMissing(
            'tenants',
            [
                'slug' => 'inactive-plan',
            ]
        );

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'inactive@example.test',
            ]
        );

        $this->assertDatabaseCount(
            'subscriptions',
            0
        );
    }

    public function test_registration_rolls_back_when_trial_start_fails(): void
    {
        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\PlanCatalogSeeder::class,
        ]);

        $this->mock(
            TrialService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('start')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Forced trial failure'
                        )
                    );
            }
        );

        $beforeTenantCount =
            \Illuminate\Support\Facades\DB::table(
                'tenants'
            )->count();

        $beforeUserCount =
            \Illuminate\Support\Facades\DB::table(
                'users'
            )->count();

        $beforeSubscriptionCount =
            \Illuminate\Support\Facades\DB::table(
                'subscriptions'
            )->count();

        $beforeFeatureCount =
            \Illuminate\Support\Facades\DB::table(
                'tenant_features'
            )->count();

        try {
            $this->post('/register', [
                'company_name' => 'Rollback Company',
                'name' => 'Rollback Admin',
                'email' => 'rollback@example.test',
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
                'country_code' => 'BR',
                'locale' => 'pt-BR',
                'plan_code' => 'start',
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced trial failure',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            $beforeTenantCount,
            \Illuminate\Support\Facades\DB::table(
                'tenants'
            )->count()
        );

        $this->assertSame(
            $beforeUserCount,
            \Illuminate\Support\Facades\DB::table(
                'users'
            )->count()
        );

        $this->assertSame(
            $beforeSubscriptionCount,
            \Illuminate\Support\Facades\DB::table(
                'subscriptions'
            )->count()
        );

        $this->assertSame(
            $beforeFeatureCount,
            \Illuminate\Support\Facades\DB::table(
                'tenant_features'
            )->count()
        );

        $this->assertDatabaseMissing(
            'tenants',
            [
                'slug' => 'rollback-company',
            ]
        );

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'rollback@example.test',
            ]
        );
    }

    public function test_registration_user_requires_email_verification(): void
    {
        $this->assertTrue(
            is_subclass_of(
                User::class,
                MustVerifyEmail::class
            )
        );
    }

    public function test_registration_sends_email_verification_notification(): void
    {
        Notification::fake();

        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\PlanCatalogSeeder::class,
        ]);

        $response = $this->post('/register', [
            'company_name' => 'Verify Email Ltda',
            'name' => 'Admin Verify',
            'email' => 'admin.verify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'plan_code' => 'start',
        ]);

        $response->assertRedirect();

        $tenant = Tenant::query()
            ->where(
                'slug',
                'verify-email-ltda'
            )
            ->firstOrFail();

        app(TenantContext::class)
            ->set($tenant);

        $user = User::query()
            ->where(
                'email',
                'admin.verify@example.com'
            )
            ->firstOrFail();

        $this->assertNull(
            $user->email_verified_at
        );

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_email_verification_notice_route_exists(): void
    {
        $routes = file_get_contents(
            base_path('routes/web.php')
        );

        $this->assertStringContainsString(
            "verification.notice",
            $routes
        );

        $this->assertStringContainsString(
            "verification.verify",
            $routes
        );

        $this->assertStringContainsString(
            "verification.send",
            $routes
        );
    }

    public function test_email_verification_notice_explains_next_step(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Notice Tenant',
            'slug' => 'notice-tenant',
            'status' => 'active',
            'locale' => 'pt-BR',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Notice User',
            'email' => 'notice.user@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this
            ->actingAs($user)
            ->get('http://notice-tenant.localhost/email/verify')
            ->assertOk()
            ->assertSee('Verifique seu e-mail')
            ->assertSee('notice.user@example.test')
            ->assertSee('Reenviar e-mail de verificação')
            ->assertSee('Sair e usar outra conta');
    }

    public function test_signed_email_verification_marks_user_verified(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Verify Tenant',
            'slug' => 'verify-tenant',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Verify User',
            'email' => 'verify.user@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->assertNull(
            $user->email_verified_at
        );

        $this->actingAs($user);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1(
                    $user->getEmailForVerification()
                ),
            ]
        );

        $response = $this->get(
            $url
        );

        $response->assertRedirect();

        $this->assertNotNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_email_verification_can_be_resent(): void
    {
        Notification::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Resend Tenant',
            'slug' => 'resend-tenant',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Resend User',
            'email' => 'resend.user@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        $response = $this->post(
            'http://resend-tenant.localhost/email/verification-notification'
        );

        $response->assertRedirect();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_email_verification_rejects_unsigned_link(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Unsigned Verify Tenant',
            'slug' => 'unsigned-verify',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Unsigned User',
            'email' => 'unsigned@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response = $this->get(
            '/email/verify/' .
            $user->getKey() .
            '/' .
            sha1($user->getEmailForVerification())
        );

        $response->assertForbidden();

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_email_verification_rejects_invalid_hash(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Invalid Hash Tenant',
            'slug' => 'invalid-hash',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Invalid Hash User',
            'email' => 'invalid.hash@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1('tampered@example.test'),
            ]
        );

        $response = $this->get($url);

        $response
            ->assertSessionHasErrors('email');

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_email_verification_does_not_verify_different_user(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Different User Tenant',
            'slug' => 'different-user',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $userA = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'User A',
            'email' => 'user.a@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $userB = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'User B',
            'email' => 'user.b@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $userB->getKey(),
                'hash' => sha1(
                    $userA->getEmailForVerification()
                ),
            ]
        );

        $response = $this->get($url);

        $response
            ->assertSessionHasErrors('email');

        $this->assertNull(
            $userA->fresh()->email_verified_at
        );

        $this->assertNull(
            $userB->fresh()->email_verified_at
        );
    }

    public function test_registration_page_presents_plan_and_trial_as_checkout_summary(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('Resumo da contratação')
            ->assertSee('Plano escolhido')
            ->assertSee('Período de avaliação')
            ->assertSee('dias de trial')
            ->assertSee('Criar conta');
    }

   public function test_registration_validation_preserves_safe_fields_and_shows_feedback(): void
{
    $response = $this
        ->followingRedirects()
        ->from('http://localhost/register')
        ->post('http://localhost/register', [
            'company_name' => 'Empresa UX',
            'name' => 'Maria UX',
            'email' => 'maria.ux@example.test',
            'password' => 'curta',
            'password_confirmation' => 'diferente',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'plan_code' => 'start',
        ]);

    $response
        ->assertOk()
        ->assertSee('Revise os campos destacados antes de continuar.')
        ->assertSee('value="Empresa UX"', false)
        ->assertSee('value="Maria UX"', false)
        ->assertSee('value="maria.ux@example.test"', false);

}

    public function test_registration_rejects_email_without_valid_domain_shape(): void
    {
        $this->seed(PlanCatalogSeeder::class);

        $response = $this
            ->from('http://localhost:8000/register')
            ->post('http://localhost:8000/register', [
                'company_name' => 'Empresa Email Invalido',
                'name' => 'Maria Email',
                'email' => 'teste@teste',
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
                'country_code' => 'BR',
                'locale' => 'pt-BR',
                'plan_code' => 'start',
            ]);

        $response
            ->assertRedirect('http://localhost:8000/register')
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('tenants', [
            'slug' => 'empresa-email-invalido',
        ]);
    }

    public function test_registration_redirect_preserves_local_request_port(): void
    {
        $this->seed([
            PermissionSeeder::class,
            PlanCatalogSeeder::class,
        ]);

        config([
            'app.url' => 'http://localhost',
        ]);

        $response = $this->post('http://localhost:8000/register', [
            'company_name' => 'Empresa Porta Local',
            'name' => 'Maria Porta',
            'email' => 'maria.porta@example.test',
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'plan_code' => 'start',
        ]);

        $response->assertRedirect(
            'http://empresa-porta-local.localhost:8000/login'
        );
    }



}
