<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AutomationActionExecutor;
use App\Services\CreateNotificationActionHandler;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class CreateNotificationActionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_persisted_for_user(): void
    {
        $tenant =
            $this->tenant(
                'notification-action-create'
            );

        $user =
            $this->user(
                'notification@example.test'
            );

        $result = app(
            CreateNotificationActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_NOTIFICATION,
                [
                    'user_id' =>
                        $user->id,

                    'title' =>
                        'Opportunity updated',

                    'message' =>
                        'The opportunity was updated.',

                    'data' => [
                        'source' =>
                            'automation',
                    ],
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $notification =
            $user->notifications()
                ->first();

        $this->assertNotNull(
            $notification
        );

        $this->assertSame(
            'Opportunity updated',
            $notification->data['title']
        );

        $this->assertSame(
            'The opportunity was updated.',
            $notification->data['message']
        );

        $this->assertSame(
            'automation',
            $notification->data['type']
        );

        $this->assertSame(
            'automation',
            $notification->data[
                'data'
            ]['source']
        );

        $this->assertNull(
            $notification->read_at
        );

        $this->assertSame(
            $notification->id,
            $result->data[
                'notification_id'
            ]
        );
    }

    public function test_executor_can_create_notification(): void
    {
        $tenant =
            $this->tenant(
                'notification-action-executor'
            );

        $user =
            $this->user(
                'executor@example.test'
            );

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::CREATE_NOTIFICATION,
            app(
                CreateNotificationActionHandler::class
            )
        );

        $result = $executor->execute(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_NOTIFICATION,
                [
                    'user_id' =>
                        $user->id,

                    'title' =>
                        'Automation',

                    'message' =>
                        'Executed.',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertCount(
            1,
            $user->notifications()
                ->get()
        );
    }

    public function test_user_id_is_required(): void
    {
        $tenant =
            $this->tenant(
                'notification-action-user-required'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateNotificationActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_NOTIFICATION,
                [
                    'title' =>
                        'Title',

                    'message' =>
                        'Message',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_title_is_required(): void
    {
        $tenant =
            $this->tenant(
                'notification-action-title'
            );

        $user =
            $this->user(
                'title@example.test'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateNotificationActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_NOTIFICATION,
                [
                    'user_id' =>
                        $user->id,

                    'title' =>
                        '   ',

                    'message' =>
                        'Message',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_message_is_required(): void
    {
        $tenant =
            $this->tenant(
                'notification-action-message'
            );

        $user =
            $this->user(
                'message@example.test'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateNotificationActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_NOTIFICATION,
                [
                    'user_id' =>
                        $user->id,

                    'title' =>
                        'Title',

                    'message' =>
                        '   ',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_numeric_string_user_id_is_supported(): void
    {
        $tenant =
            $this->tenant(
                'notification-action-string'
            );

        $user =
            $this->user(
                'numeric@example.test'
            );

        $result = app(
            CreateNotificationActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_NOTIFICATION,
                [
                    'user_id' =>
                        (string) $user->id,

                    'title' =>
                        'Title',

                    'message' =>
                        'Message',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );

        $this->assertTrue(
            $result->successful
        );
    }

    public function test_user_from_other_tenant_is_rejected(): void
    {
        $tenantA =
            $this->tenant(
                'notification-action-tenant-a'
            );

        $tenantB =
            $this->tenant(
                'notification-action-tenant-b'
            );

        $userB =
            $this->user(
                'tenant-b@example.test'
            );

        app(TenantContext::class)
            ->set(
                $tenantA
            );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            CreateNotificationActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenantA->id,
                AutomationActionType::CREATE_NOTIFICATION,
                [
                    'user_id' =>
                        $userB->id,

                    'title' =>
                        'Title',

                    'message' =>
                        'Message',
                ]
            ),
            [
                'tenant_id' =>
                    $tenantA->id,
            ]
        );
    }

    public function test_wrong_action_type_is_rejected(): void
    {
        $tenant =
            $this->tenant(
                'notification-action-wrong-type'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateNotificationActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_TASK,
                []
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()
            ->create([
                'name' =>
                    'Tenant ' . $slug,

                'slug' =>
                    $slug,

                'status' =>
                    'active',

                'country_code' =>
                    'BR',

                'locale' =>
                    'pt-BR',

                'timezone' =>
                    'America/Fortaleza',

                'currency' =>
                    'BRL',
            ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        return $tenant;
    }

    private function user(
        string $email
    ): User {
        return User::query()
            ->create([
                'name' =>
                    'Automation User',

                'email' =>
                    $email,

                'password' =>
                    Hash::make(
                        'password123'
                    ),

                'role' =>
                    'user',
            ]);
    }
}