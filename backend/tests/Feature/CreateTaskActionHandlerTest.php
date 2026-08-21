<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\AutomationActionType;
use App\Enums\CustomerType;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AutomationActionExecutor;
use App\Services\CreateTaskActionHandler;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class CreateTaskActionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_task_action_creates_pending_task(): void
    {
        $tenant =
            $this->tenant(
                'action-task-create'
            );

        $handler = app(
            CreateTaskActionHandler::class
        );

        $result = $handler->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_TASK,
                [
                    'title' =>
                        'Follow up cliente',

                    'description' =>
                        'Entrar em contato amanhã.',

                    'due_at' =>
                        '2026-08-20 10:00:00',
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

        $activity =
            Activity::query()
                ->findOrFail(
                    $result->data[
                        'activity_id'
                    ]
                );

        $this->assertSame(
            $tenant->id,
            $activity->tenant_id
        );

        $this->assertSame(
            ActivityType::TASK,
            $activity->type
        );

        $this->assertSame(
            ActivityStatus::PENDING,
            $activity->status
        );

        $this->assertSame(
            'Follow up cliente',
            $activity->title
        );

        $this->assertSame(
            'Entrar em contato amanhã.',
            $activity->description
        );

        $this->assertNotNull(
            $activity->due_at
        );
    }

    public function test_create_task_can_link_customer_and_responsible(): void
    {
        $tenant =
            $this->tenant(
                'action-task-relations'
            );

        $customer =
            $this->customer();

        $responsible =
            $this->user(
                'responsible@action.local'
            );

        $result = app(
            CreateTaskActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_TASK,
                [
                    'title' =>
                        'Contato comercial',

                    'customer_id' =>
                        $customer->id,

                    'responsible_user_id' =>
                        $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );

        $activity =
            Activity::query()
                ->findOrFail(
                    $result->data[
                        'activity_id'
                    ]
                );

        $this->assertSame(
            $customer->id,
            $activity->customer_id
        );

        $this->assertSame(
            $responsible->id,
            $activity->responsible_user_id
        );
    }

    public function test_executor_can_execute_create_task_handler(): void
    {
        $tenant =
            $this->tenant(
                'action-task-executor'
            );

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::CREATE_TASK,
            app(
                CreateTaskActionHandler::class
            )
        );

        $result = $executor->execute(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_TASK,
                [
                    'title' =>
                        'Criada pela automação',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,

                'trigger' => [
                    'name' =>
                        'lead.created',
                ],
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertDatabaseHas(
            'activities',
            [
                'id' =>
                    $result->data[
                        'activity_id'
                    ],

                'tenant_id' =>
                    $tenant->id,

                'type' =>
                    ActivityType::TASK->value,

                'title' =>
                    'Criada pela automação',
            ]
        );
    }

    public function test_blank_title_is_rejected_by_activity_domain(): void
    {
        $tenant =
            $this->tenant(
                'action-task-title'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateTaskActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_TASK,
                [
                    'title' => '   ',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_customer_from_other_tenant_is_rejected(): void
    {
        $tenantA =
            $this->tenant(
                'action-task-customer-a'
            );

        $customer =
            $this->customer();

        $tenantB =
            $this->tenant(
                'action-task-customer-b'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateTaskActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenantB->id,
                AutomationActionType::CREATE_TASK,
                [
                    'title' =>
                        'Cross tenant',

                    'customer_id' =>
                        $customer->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenantB->id,
            ]
        );

        app(TenantContext::class)
            ->set($tenantA);
    }

    public function test_responsible_from_other_tenant_is_rejected(): void
    {
        $this->tenant(
            'action-task-user-a'
        );

        $responsible =
            $this->user(
                'foreign-responsible@action.local'
            );

        $tenantB =
            $this->tenant(
                'action-task-user-b'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateTaskActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenantB->id,
                AutomationActionType::CREATE_TASK,
                [
                    'title' =>
                        'Cross tenant responsible',

                    'responsible_user_id' =>
                        $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenantB->id,
            ]
        );
    }

    public function test_wrong_action_type_is_rejected(): void
    {
        $tenant =
            $this->tenant(
                'action-task-wrong-type'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            CreateTaskActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_EMAIL,
                [
                    'title' =>
                        'Nao deve criar',
                ]
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
                    'Responsible',

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

    private function customer(): Customer
    {
        return Customer::query()
            ->create([
                'type' =>
                    CustomerType::COMPANY,

                'name' =>
                    'Cliente automação',
            ]);
    }
}