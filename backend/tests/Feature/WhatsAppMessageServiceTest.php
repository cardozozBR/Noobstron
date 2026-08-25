<?php

namespace Tests\Feature;

use App\Enums\WhatsAppMessageStatus;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_message_can_be_created(): void
    {
        $this->tenant(
            'whatsapp-service-create'
        );

        $message = $this->service()
            ->create([
                'phone' => '+55 85 99999-9999',

                'recipient_name' => 'Cliente',

                'body' => 'Olá pelo WhatsApp',
            ]);

        $this->assertSame(
            '5585999999999',
            $message->phone
        );

        $this->assertSame(
            WhatsAppMessageStatus::PENDING,
            $message->status
        );
    }

    public function test_failed_whatsapp_message_can_be_retried(): void
    {
        $tenant = $this->tenant(
            'whatsapp-retry'
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '5511999999999',
                'body' => 'Mensagem para retry.',
                'provider' => 'meta',
                'status' => WhatsAppMessageStatus::FAILED,
                'provider_message_id' => 'provider-message-old',
                'sent_at' => now()->subMinute(),
                'delivered_at' => now()->subMinute(),
                'read_at' => now()->subMinute(),
                'failed_at' => now(),
                'failure_reason' => 'Temporary provider failure.',
            ]);

        $message = $service->retry(
            $message
        );

        $this->assertSame(
            WhatsAppMessageStatus::PENDING,
            $message->status
        );

        $this->assertSame(
            'meta',
            $message->provider
        );

        $this->assertNull(
            $message->provider_message_id
        );

        $this->assertNull(
            $message->sent_at
        );

        $this->assertNull(
            $message->delivered_at
        );

        $this->assertNull(
            $message->read_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_reason
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'whatsapp.retried',
            ]
        );
    }

    public function test_failed_whatsapp_message_without_provider_cannot_be_retried(): void
    {
        $this->tenant(
            'whatsapp-retry-no-provider'
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '5511888888888',
                'body' => 'Mensagem sem provider.',
                'status' => WhatsAppMessageStatus::FAILED,
                'failed_at' => now(),
                'failure_reason' => 'Temporary failure.',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'WhatsApp provider is required to retry message.'
        );

        $service->retry(
            $message
        );
    }

    public function test_pending_message_can_be_marked_as_sent(): void
    {
        $this->tenant(
            'whatsapp-service-sent'
        );

        $message = $this->message();

        $updated = $this->service()
            ->markSent(
                $message,
                'meta',
                'wamid.sent.1'
            );

        $this->assertSame(
            WhatsAppMessageStatus::SENT,
            $updated->status
        );

        $this->assertSame(
            'meta',
            $updated->provider
        );

        $this->assertSame(
            'wamid.sent.1',
            $updated->provider_message_id
        );

        $this->assertNotNull(
            $updated->sent_at
        );
    }

    public function test_sent_message_can_be_marked_as_delivered(): void
    {
        $this->tenant(
            'whatsapp-service-delivered'
        );

        $message = $this->service()
            ->markSent(
                $this->message()
            );

        $updated = $this->service()
            ->markDelivered(
                $message
            );

        $this->assertSame(
            WhatsAppMessageStatus::DELIVERED,
            $updated->status
        );

        $this->assertNotNull(
            $updated->delivered_at
        );
    }

    public function test_delivered_message_can_be_marked_as_read(): void
    {
        $this->tenant(
            'whatsapp-service-read'
        );

        $message = $this->service()
            ->markDelivered(
                $this->service()
                    ->markSent(
                        $this->message()
                    )
            );

        $updated = $this->service()
            ->markRead(
                $message
            );

        $this->assertSame(
            WhatsAppMessageStatus::READ,
            $updated->status
        );

        $this->assertNotNull(
            $updated->read_at
        );
    }

    public function test_pending_message_can_be_marked_as_failed(): void
    {
        $this->tenant(
            'whatsapp-service-failed'
        );

        $message = $this->message();

        $updated = $this->service()
            ->markFailed(
                $message,
                'Provider unavailable'
            );

        $this->assertSame(
            WhatsAppMessageStatus::FAILED,
            $updated->status
        );

        $this->assertSame(
            'Provider unavailable',
            $updated->failure_reason
        );

        $this->assertNotNull(
            $updated->failed_at
        );
    }

    public function test_failure_reason_is_required(): void
    {
        $this->tenant(
            'whatsapp-service-failure-reason'
        );

        $message = $this->message();

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markFailed(
                $message,
                '   '
            );
    }

    public function test_inbound_message_can_be_received(): void
    {
        $this->tenant(
            'whatsapp-service-receive'
        );

        $message = $this->service()
            ->receive([
                'phone' => '+55 85 98888-7777',

                'recipient_name' => 'Cliente',

                'body' => 'Mensagem recebida',

                'provider' => 'meta',

                'provider_message_id' => 'wamid.inbound.1',
            ]);

        $this->assertSame(
            WhatsAppMessageStatus::RECEIVED,
            $message->status
        );

        $this->assertSame(
            'inbound',
            $message->direction
        );

        $this->assertNotNull(
            $message->received_at
        );
    }

    public function test_sent_message_cannot_be_marked_as_sent_again(): void
    {
        $this->tenant(
            'whatsapp-service-repeat-send'
        );

        $message = $this->service()
            ->markSent(
                $this->message()
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markSent(
                $message
            );
    }

    public function test_pending_message_cannot_be_marked_as_delivered(): void
    {
        $this->tenant(
            'whatsapp-service-invalid-delivery'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markDelivered(
                $this->message()
            );
    }

    public function test_sent_message_cannot_be_marked_as_read(): void
    {
        $this->tenant(
            'whatsapp-service-invalid-read'
        );

        $message = $this->service()
            ->markSent(
                $this->message()
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markRead(
                $message
            );
    }

    public function test_delivered_message_cannot_be_marked_as_failed(): void
    {
        $this->tenant(
            'whatsapp-service-invalid-failed'
        );

        $message = $this->service()
            ->markDelivered(
                $this->service()
                    ->markSent(
                        $this->message()
                    )
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markFailed(
                $message,
                'Late failure'
            );
    }

    public function test_invalid_transition_does_not_change_message(): void
    {
        $this->tenant(
            'whatsapp-service-atomic'
        );

        $message = $this->message();

        try {
            $this->service()
                ->markRead(
                    $message
                );
        } catch (RuntimeException) {
        }

        $fresh = $message->fresh();

        $this->assertSame(
            WhatsAppMessageStatus::PENDING,
            $fresh->status
        );

        $this->assertNull(
            $fresh->read_at
        );
    }

    public function test_other_tenant_message_cannot_be_marked_as_sent(): void
    {
        $tenantA = $this->tenant(
            'whatsapp-service-tenant-a'
        );

        $message = $this->message();

        $this->tenant(
            'whatsapp-service-tenant-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markSent(
                $message
            );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    public function test_other_tenant_message_cannot_be_marked_as_delivered(): void
    {
        $tenantA = $this->tenant(
            'whatsapp-service-delivery-a'
        );

        $message = $this->service()
            ->markSent(
                $this->message()
            );

        $this->tenant(
            'whatsapp-service-delivery-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markDelivered(
                $message
            );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    public function test_other_tenant_message_cannot_be_marked_as_failed(): void
    {
        $tenantA = $this->tenant(
            'whatsapp-service-failed-a'
        );

        $message = $this->message();

        $this->tenant(
            'whatsapp-service-failed-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->markFailed(
                $message,
                'Provider error'
            );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    private function service(): WhatsAppMessageService
    {
        return app(
            WhatsAppMessageService::class
        );
    }

    private function message(): WhatsAppMessage
    {
        return WhatsAppMessage::query()
            ->create([
                'phone' => '5585999999999',

                'recipient_name' => 'Cliente',

                'body' => 'Mensagem WhatsApp',
            ]);
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()
            ->create([
                'name' => 'Tenant '.$slug,

                'slug' => $slug,

                'status' => 'active',

                'country_code' => 'BR',

                'locale' => 'pt-BR',

                'timezone' => 'America/Fortaleza',

                'currency' => 'BRL',
            ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        return $tenant;
    }
}
