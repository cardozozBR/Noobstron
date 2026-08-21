<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ActivityDueReminder;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationHttpTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)
            ->set($tenant);

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)
            ->set($tenant);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Notification User',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    public function test_notification_routes_require_authentication(): void
    {
        $tenant = $this->tenant(
            'notification-auth'
        );

        $this->get(
            "http://{$tenant->slug}.localhost/notifications"
        )->assertRedirect('/login');
    }

    public function test_user_can_access_notification_inbox(): void
    {
        $tenant = $this->tenant(
            'notification-index'
        );

        $user = $this->user(
            $tenant,
            'notification-index@local'
        );

        $this->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/notifications"
            )
            ->assertOk();
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $tenant = $this->tenant(
            'notification-read'
        );

        $user = $this->user(
            $tenant,
            'notification-read@local'
        );

        $notification =
            $this->createNotificationFor(
                $user
            );

        $this->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/notifications/{$notification->id}/read"
            )
            ->assertRedirect();

        $this->assertNotNull(
            $notification
                ->fresh()
                ->read_at
        );
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $tenant = $this->tenant(
            'notification-isolation'
        );

        $owner = $this->user(
            $tenant,
            'notification-owner@local'
        );

        $other = $this->user(
            $tenant,
            'notification-other@local'
        );

        $notification =
            $this->createNotificationFor(
                $owner
            );

        $this->actingAs($other)
            ->post(
                "http://{$tenant->slug}.localhost/notifications/{$notification->id}/read"
            )
            ->assertNotFound();

        $this->assertNull(
            $notification
                ->fresh()
                ->read_at
        );
    }

    public function test_mark_all_only_marks_authenticated_users_notifications(): void
    {
        $tenant = $this->tenant(
            'notification-read-all'
        );

        $user = $this->user(
            $tenant,
            'notification-read-all-user@local'
        );

        $other = $this->user(
            $tenant,
            'notification-read-all-other@local'
        );

        $own =
            $this->createNotificationFor(
                $user
            );

        $foreign =
            $this->createNotificationFor(
                $other
            );

        $this->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/notifications/read-all"
            )
            ->assertRedirect();

        $this->assertNotNull(
            $own->fresh()->read_at
        );

        $this->assertNull(
            $foreign->fresh()->read_at
        );
    }

    public function test_notification_inbox_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'notification-tenant-a'
        );

        $userA = $this->user(
            $tenantA,
            'notification-a@local'
        );

        $this->createNotificationFor(
            $userA,
            'Tenant A notification'
        );

        $tenantB = $this->tenant(
            'notification-tenant-b'
        );

        $userB = $this->user(
            $tenantB,
            'notification-b@local'
        );

        $this->createNotificationFor(
            $userB,
            'Tenant B notification'
        );

        app(TenantContext::class)
            ->set($tenantA);

        $response = $this
            ->actingAs($userA)
            ->get(
                "http://{$tenantA->slug}.localhost/notifications"
            );

        $response->assertOk();
        $response->assertSee(
            'Tenant A notification'
        );
        $response->assertDontSee(
            'Tenant B notification'
        );
    }

    private function createNotificationFor(
        User $user,
        string $title = 'Follow up'
    ): DatabaseNotification {
        return $user
            ->notifications()
            ->create([
                'id' => (string) Str::uuid(),

                'type' =>
                    ActivityDueReminder::class,

                'data' => [
                    'activity_id' => 999,
                    'title' => $title,
                    'type' => 'task',
                    'due_at' =>
                        now()
                            ->addHour()
                            ->toIso8601String(),
                    'customer_id' => null,
                    'opportunity_id' => null,
                ],

                'read_at' => null,
            ]);
    }
}
