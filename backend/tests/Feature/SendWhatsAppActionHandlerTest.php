<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Enums\WhatsAppMessageStatus;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\AutomationActionExecutor;
use App\Services\SendWhatsAppActionHandler;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class SendWhatsAppActionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_whatsapp_action_creates_pending_message_and_queues_it(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-whatsapp-create'
            );

        $result = app(
            SendWhatsAppActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_WHATSAPP,
                [
                    'provider' =>
                        'META',

                    'phone' =>
                        '+5585999999999',

                    'recipient_name' =>
                        'Cliente Teste',

                    'body' =>
                        'Mensagem automatizada',
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

        $this->assertSame(
            'meta',
            $result->data['provider']
        );

        $message =
            WhatsAppMessage::query()
                ->findOrFail(
                    $result->data[
                        'whatsapp_message_id'
                    ]
                );

        $this->assertSame(
            $tenant->id,
            $message->tenant_id
        );

        $this->assertSame(
            '5585999999999',
            $message->phone
        );

        $this->assertSame(
            'Cliente Teste',
            $message->recipient_name
        );

        $this->assertSame(
            'Mensagem automatizada',
            $message->body
        );

        $this->assertSame(
            WhatsAppMessageStatus::PENDING,
            $message->status
        );

        Queue::assertPushed(
            SendWhatsAppMessageJob::class,
            function (
                SendWhatsAppMessageJob $job
            ): bool {
                return
                    $job->provider ===
                    'meta';
            }
        );
    }

    public function test_executor_can_execute_send_whatsapp_handler(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-whatsapp-executor'
            );

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::SEND_WHATSAPP,
            app(
                SendWhatsAppActionHandler::class
            )
        );

        $result = $executor->execute(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_WHATSAPP,
                [
                    'provider' =>
                        'meta',

                    'phone' =>
                        '+5585888888888',

                    'body' =>
                        'Mensagem via executor',
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
            WhatsAppMessageStatus::PENDING->value,
            $result->data['status']
        );

        $this->assertDatabaseHas(
            'whatsapp_messages',
            [
                'id' =>
                    $result->data[
                        'whatsapp_message_id'
                    ],

                'tenant_id' =>
                    $tenant->id,

                'body' =>
                    'Mensagem via executor',

                'status' =>
                    WhatsAppMessageStatus::PENDING
                        ->value,
            ]
        );

        Queue::assertPushed(
            SendWhatsAppMessageJob::class,
            1
        );
    }

    public function test_provider_is_required(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-whatsapp-provider'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWhatsAppActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_WHATSAPP,
                [
                    'provider' =>
                        '   ',

                    'phone' =>
                        '+5585999999999',

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

    public function test_blank_phone_is_rejected_by_whatsapp_domain(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-whatsapp-phone'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWhatsAppActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_WHATSAPP,
                [
                    'provider' =>
                        'meta',

                    'phone' =>
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

    public function test_blank_body_is_rejected_by_whatsapp_domain(): void
    {
        Queue::fake();

        $tenant =
            $this->tenant(
                'action-whatsapp-body'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWhatsAppActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_WHATSAPP,
                [
                    'provider' =>
                        'meta',

                    'phone' =>
                        '+5585999999999',

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
                'action-whatsapp-not-queued'
            );

        try {
            app(
                SendWhatsAppActionHandler::class
            )->handle(
                AutomationAction::make(
                    $tenant->id,
                    AutomationActionType::SEND_WHATSAPP,
                    [
                        'provider' =>
                            'meta',

                        'phone' =>
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
        } catch (RuntimeException) {
            // Expected domain rejection.
        }

        Queue::assertNothingPushed();
    }

    public function test_executor_rejects_tenant_mismatch_before_message_creation(): void
    {
        Queue::fake();

        $tenantA =
            $this->tenant(
                'action-whatsapp-tenant-a'
            );

        $tenantB = Tenant::query()
            ->create([
                'name' =>
                    'Tenant B',

                'slug' =>
                    'action-whatsapp-tenant-b',

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
            AutomationActionType::SEND_WHATSAPP,
            app(
                SendWhatsAppActionHandler::class
            )
        );

        try {
            $executor->execute(
                AutomationAction::make(
                    $tenantA->id,
                    AutomationActionType::SEND_WHATSAPP,
                    [
                        'provider' =>
                            'meta',

                        'phone' =>
                            '+5585999999999',

                        'body' =>
                            'Nao enviar',
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
                'whatsapp_messages',
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
                'action-whatsapp-wrong-type'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWhatsAppActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::SEND_EMAIL,
                [
                    'provider' =>
                        'meta',

                    'phone' =>
                        '+5585999999999',

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