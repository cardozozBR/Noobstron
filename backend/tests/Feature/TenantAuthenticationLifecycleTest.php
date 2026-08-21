<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class TenantAuthenticationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_tenant_user_can_login_and_open_dashboard_after_guard_reload(): void
    {
        [$tenant, $user] = $this->tenantUser(true);

        $login = $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                'email' => $user->email,
                'password' => 'password123',
            ]
        );

        $login->assertRedirect('/dashboard');

        app(TenantContext::class)->clear();
        Auth::forgetGuards();

        $this->get("http://{$tenant->slug}.localhost/dashboard")
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_unverified_tenant_user_reaches_verification_notice_without_500(): void
    {
        [$tenant, $user] = $this->tenantUser(false);

        $login = $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                'email' => $user->email,
                'password' => 'password123',
            ]
        );

        $login->assertRedirect('/dashboard');

        app(TenantContext::class)->clear();
        Auth::forgetGuards();

        $dashboard = $this->get(
            "http://{$tenant->slug}.localhost/dashboard"
        );

        $dashboard->assertRedirect(
            "http://{$tenant->slug}.localhost/email/verify"
        );

        app(TenantContext::class)->clear();
        Auth::forgetGuards();

        $this->get("http://{$tenant->slug}.localhost/email/verify")
            ->assertOk()
            ->assertSee('Verifique seu e-mail');
    }

    public function test_verification_notification_can_be_resent_on_tenant_host_after_guard_reload(): void
    {
        Notification::fake();

        [$tenant, $user] = $this->tenantUser(false);

        $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                'email' => $user->email,
                'password' => 'password123',
            ]
        );

        app(TenantContext::class)->clear();
        Auth::forgetGuards();

        $this->post(
            "http://{$tenant->slug}.localhost/email/verification-notification"
        )->assertRedirect();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }


    public function test_authenticated_tenant_session_can_visit_public_root_without_500(): void
    {
        [$tenant, $user] = $this->tenantUser(true);

        $this->post(
            "http://{$tenant->slug}.localhost/login",
            [
                'email' => $user->email,
                'password' => 'password123',
            ]
        )->assertRedirect('/dashboard');

        app(TenantContext::class)->clear();
        Auth::forgetGuards();

        $this->get("http://{$tenant->slug}.localhost/")
            ->assertRedirect('/dashboard');
    }

    /**
     * @return array{Tenant, User}
     */
    private function tenantUser(bool $verified): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant Auth Lifecycle',
            'slug' => 'tenant-auth-lifecycle',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::query()->create([
            'name' => 'Lifecycle User',
            'email' => 'lifecycle@example.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        if ($verified) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        app(TenantContext::class)->clear();

        return [$tenant, $user];
    }
}
