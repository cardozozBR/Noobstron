<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TenantPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_password_reset_request_page_is_available(): void
    {
        $tenant = $this->tenant('tenant-reset-page');

        $this->get("http://{$tenant->slug}.localhost/forgot-password")
            ->assertOk()
            ->assertSee('Recuperar senha');
    }

    public function test_reset_link_is_sent_only_for_user_in_current_tenant(): void
    {
        Notification::fake();

        $tenantA = $this->tenant('tenant-reset-a');
        $tenantB = $this->tenant('tenant-reset-b');

        $userA = $this->user($tenantA, 'same@example.test');
        $userB = $this->user($tenantB, 'same@example.test');

        app(TenantContext::class)->clear();

        $this->post(
            "http://{$tenantA->slug}.localhost/forgot-password",
            ['email' => 'same@example.test']
        )->assertSessionHasNoErrors();

        Notification::assertSentTo($userA, ResetPassword::class);
        Notification::assertNotSentTo($userB, ResetPassword::class);
    }

    public function test_tenant_user_can_reset_password_with_valid_token(): void
    {
        Notification::fake();

        $tenant = $this->tenant('tenant-reset-success');
        $user = $this->user($tenant, 'reset@example.test');

        app(TenantContext::class)->clear();

        $this->post(
            "http://{$tenant->slug}.localhost/forgot-password",
            ['email' => $user->email]
        )->assertSessionHasNoErrors();

        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->assertIsString($token);

        app(TenantContext::class)->clear();

        $this->post(
            "http://{$tenant->slug}.localhost/reset-password",
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]
        )->assertRedirect('/login');

        app(TenantContext::class)->set($tenant);

        $this->assertTrue(
            Hash::check(
                'new-password-123',
                $user->fresh()->password
            )
        );
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function user(Tenant $tenant, string $email): User
    {
        app(TenantContext::class)->set($tenant);

        return User::query()->create([
            'name' => 'Password Reset User',
            'email' => $email,
            'password' => Hash::make('old-password-123'),
            'role' => 'user',
        ]);
    }
}
