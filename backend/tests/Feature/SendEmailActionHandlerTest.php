<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Enums\EmailMessageStatus;
use App\Jobs\SendEmailMessageJob;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Services\AutomationActionExecutor;
use App\Services\SendEmailActionHandler;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class SendEmailActionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_email_action_creates_pending_message_and_queues_it(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-email-create'
            );

        $result = app(
            SendEmailActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_EMAIL,
                [
                    'to_email' =>
                        'CLIENT@EXAMPLE.TEST',

                    'to_name' =>
                        'Cliente Teste',

                    'subject' =>
                        'Follow up',

                    'body' =>
                        'Olá, este é um follow up.',
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

        $this->assertTrue(
            $result->data['queued']
        );

        $message =
            EmailMessage::query()
                ->findOrFail(
                    $result->data[
                        'email_message_id'
                    ]
                );

        $this->assertSame(
            $tenant->id,
            $message->tenant_id
        );

        $this->assertSame(
            'client@example.test',
            $message->to_email
        );

        $this->assertSame(
            'Cliente Teste',
            $message->to_name
        );

        $this->assertSame(
            'Follow up',
            $message->subject
        );

        $this->assertSame(
            'Olá, este é um follow up.',
            $message->body
        );

        $this->assertSame(
            EmailMessageStatus::PENDING,
            $message->status
        );

        Queue::assertPushed(
            SendEmailMessageJob::class,
            function (
                SendEmailMessageJob $job
            ) use (
                $tenant,
                $message
            ): bool {
                return
                    $job->tenantId ===
                        $tenant->id
                    &&
                    $job->emailMessageId ===
                        $message->id;
            }
        );
    }

    public function test_executor_can_execute_send_email_handler(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-email-executor'
            );

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::SEND_EMAIL,
            app(
                SendEmailActionHandler::class
            )
        );

        $result = $executor->execute(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_EMAIL,
                [
                    'to_email' =>
                        'customer@example.test',

                    'subject' =>
                        'Automação',

                    'body' =>
                        'Mensagem automatizada',
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

        $this->assertSame(
            EmailMessageStatus::PENDING->value,
            $result->data['status']
        );

        $this->assertDatabaseHas(
            'email_messages',
            [
                'id' =>
                    $result->data[
                        'email_message_id'
                    ],

                'tenant_id' =>
                    $tenant->id,

                'to_email' =>
                    'customer@example.test',

                'status' =>
                    EmailMessageStatus::PENDING
                        ->value,
            ]
        );

        Queue::assertPushed(
            SendEmailMessageJob::class,
            1
        );
    }

    public function test_invalid_email_is_rejected_by_email_domain(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-email-invalid'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendEmailActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_EMAIL,
                [
                    'to_email' =>
                        'invalid-email',

                    'subject' =>
                        'Assunto',

                    'body' =>
                        'Mensagem',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_blank_subject_is_rejected_by_email_domain(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-email-subject'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendEmailActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_EMAIL,
                [
                    'to_email' =>
                        'customer@example.test',

                    'subject' =>
                        '   ',

                    'body' =>
                        'Mensagem',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_blank_body_is_rejected_by_email_domain(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-email-body'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendEmailActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_EMAIL,
                [
                    'to_email' =>
                        'customer@example.test',

                    'subject' =>
                        'Assunto',

                    'body' =>
                        '   ',
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_invalid_message_is_not_queued(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-email-not-queued'
            );

        try {
            app(
                SendEmailActionHandler::class
            )->handle(
                AutomationAction::make(
                    $tenant->id,
                    AutomationActionType::SEND_EMAIL,
                    [
                        'to_email' =>
                            'invalid',

                        'subject' =>
                            'Assunto',

                        'body' =>
                            'Mensagem',
                    ]
                ),
                [
                    'tenant_id' =>
                        $tenant->id,
                ]
            );
        } catch (RuntimeException) {
            // Expected domain rejection.
        }

        Queue::assertNothingPushed();
    }

    public function test_executor_rejects_tenant_mismatch_before_email_creation(): void
    {
        Queue::fake();

        $tenantA =
            $this->tenant(
                'action-email-tenant-a'
            );

        $tenantB = Tenant::query()
            ->create([
                'name' =>
                    'Tenant B',

                'slug' =>
                    'action-email-tenant-b',

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

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::SEND_EMAIL,
            app(
                SendEmailActionHandler::class
            )
        );

        try {
            $executor->execute(
                AutomationAction::make(
                    $tenantA->id,
                    AutomationActionType::SEND_EMAIL,
                    [
                        'to_email' =>
                            'customer@example.test',

                        'subject' =>
                            'Não enviar',

                        'body' =>
                            'Tenant mismatch',
                    ]
                ),
                [
                    'tenant_id' =>
                        $tenantB->id,
                ]
            );

            $this->fail(
                'Tenant mismatch should have been rejected.'
            );
        } catch (RuntimeException) {
            $this->assertDatabaseCount(
                'email_messages',
                0
            );
        }

        Queue::assertNothingPushed();
    }

    public function test_wrong_action_type_is_rejected(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-email-wrong-type'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendEmailActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_TASK,
                [
                    'to_email' =>
                        'customer@example.test',

                    'subject' =>
                        'Assunto',

                    'body' =>
                        'Mensagem',
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
}