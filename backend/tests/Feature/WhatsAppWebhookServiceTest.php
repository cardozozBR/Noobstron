<?php

namespace Tests\Feature;

use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppWebhookEventType;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use App\Services\WhatsAppMessageService;
use App\Services\WhatsAppWebhookService;
use App\Support\WhatsAppWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_webhook_creates_received_message(): void
    {
        $this->tenant(
            'webhook-inbound'
        );

        $message = $this->service()
            ->handle(
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::RECEIVED,
                    ' META ',
                    ' inbound-1 ',
                    '+55 85 99999-9999',
                    'Olá pelo webhook',
                    'Cliente'
                )
            );

        $this->assertSame(
            WhatsAppMessageStatus::RECEIVED,
            $message->status
        );

        $this->assertSame(
            'inbound',
            $message->direction
        );

        $this->assertSame(
            'meta',
            $message->provider
        );

        $this->assertSame(
            'inbound-1',
            $message->provider_message_id
        );
    }

    public function test_duplicate_inbound_webhook_is_idempotent(): void
    {
        $this->tenant(
            'webhook-inbound-idempotent'
        );

        $event = new WhatsAppWebhookEvent(
            WhatsAppWebhookEventType::RECEIVED,
            'meta',
            'inbound-idempotent',
            '5585999999999',
            'Mensagem'
        );

        $first = $this->service()
            ->handle(
                $event
            );

        $second = $this->service()
            ->handle(
                $event
            );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            1,
            WhatsAppMessage::query()
                ->count()
        );
    }

    public function test_delivered_webhook_updates_sent_message(): void
    {
        $this->tenant(
            'webhook-delivered'
        );

        $message = $this->sentMessage(
            'delivery-1'
        );

        $updated = $this->service()
            ->handle(
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::DELIVERED,
                    'meta',
                    'delivery-1'
                )
            );

        $this->assertSame(
            $message->id,
            $updated->id
        );

        $this->assertSame(
            WhatsAppMessageStatus::DELIVERED,
            $updated->status
        );

        $this->assertNotNull(
            $updated->delivered_at
        );
    }

    public function test_duplicate_delivered_webhook_is_idempotent(): void
    {
        $this->tenant(
            'webhook-delivered-idempotent'
        );

        $this->sentMessage(
            'delivery-idempotent'
        );

        $event = new WhatsAppWebhookEvent(
            WhatsAppWebhookEventType::DELIVERED,
            'meta',
            'delivery-idempotent'
        );

        $first = $this->service()
            ->handle(
                $event
            );

        $second = $this->service()
            ->handle(
                $event
            );

        $this->assertSame(
            WhatsAppMessageStatus::DELIVERED,
            $first->status
        );

        $this->assertSame(
            WhatsAppMessageStatus::DELIVERED,
            $second->status
        );
    }

    public function test_read_webhook_can_advance_sent_message_through_delivered(): void
    {
        $this->tenant(
            'webhook-read'
        );

        $this->sentMessage(
            'read-1'
        );

        $updated = $this->service()
            ->handle(
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::READ,
                    'meta',
                    'read-1'
                )
            );

        $this->assertSame(
            WhatsAppMessageStatus::READ,
            $updated->status
        );

        $this->assertNotNull(
            $updated->delivered_at
        );

        $this->assertNotNull(
            $updated->read_at
        );
    }

    public function test_duplicate_read_webhook_is_idempotent(): void
    {
        $this->tenant(
            'webhook-read-idempotent'
        );

        $this->sentMessage(
            'read-idempotent'
        );

        $event = new WhatsAppWebhookEvent(
            WhatsAppWebhookEventType::READ,
            'meta',
            'read-idempotent'
        );

        $this->service()
            ->handle(
                $event
            );

        $again = $this->service()
            ->handle(
                $event
            );

        $this->assertSame(
            WhatsAppMessageStatus::READ,
            $again->status
        );
    }

    public function test_failed_webhook_updates_pending_message(): void
    {
        $this->tenant(
            'webhook-failed'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem',

                'provider' =>
                    'meta',

                'provider_message_id' =>
                    'failed-1',
            ]);

        $updated = $this->service()
            ->handle(
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::FAILED,
                    'meta',
                    'failed-1',
                    failureReason:
                        'Provider rejected message'
                )
            );

        $this->assertSame(
            $message->id,
            $updated->id
        );

        $this->assertSame(
            WhatsAppMessageStatus::FAILED,
            $updated->status
        );

        $this->assertSame(
            'Provider rejected message',
            $updated->failure_reason
        );
    }

    public function test_duplicate_failed_webhook_is_idempotent(): void
    {
        $this->tenant(
            'webhook-failed-idempotent'
        );

        WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem',

                'provider' =>
                    'meta',

                'provider_message_id' =>
                    'failed-idempotent',
            ]);

        $event = new WhatsAppWebhookEvent(
            WhatsAppWebhookEventType::FAILED,
            'meta',
            'failed-idempotent',
            failureReason:
                'Rejected'
        );

        $this->service()
            ->handle(
                $event
            );

        $again = $this->service()
            ->handle(
                $event
            );

        $this->assertSame(
            WhatsAppMessageStatus::FAILED,
            $again->status
        );
    }

    public function test_unknown_status_message_is_rejected(): void
    {
        $this->tenant(
            'webhook-missing'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->handle(
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::DELIVERED,
                    'meta',
                    'does-not-exist'
                )
            );
    }

    public function test_failed_event_requires_failure_reason(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        new WhatsAppWebhookEvent(
            WhatsAppWebhookEventType::FAILED,
            'meta',
            'failed-no-reason'
        );
    }

    public function test_received_event_requires_phone(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        new WhatsAppWebhookEvent(
            WhatsAppWebhookEventType::RECEIVED,
            'meta',
            'inbound-no-phone',
            null,
            'Mensagem'
        );
    }

    public function test_received_event_requires_body(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        new WhatsAppWebhookEvent(
            WhatsAppWebhookEventType::RECEIVED,
            'meta',
            'inbound-no-body',
            '5585999999999',
            null
        );
    }

    public function test_webhook_cannot_update_message_from_other_tenant(): void
    {
        $tenantA = $this->tenant(
            'webhook-tenant-a'
        );

        $this->sentMessage(
            'tenant-isolation'
        );

        $this->tenant(
            'webhook-tenant-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->handle(
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::DELIVERED,
                    'meta',
                    'tenant-isolation'
                )
            );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    private function service(): WhatsAppWebhookService
    {
        return app(
            WhatsAppWebhookService::class
        );
    }

    private function sentMessage(
        string $providerMessageId
    ): WhatsAppMessage {
        $message = WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem',

                'provider' =>
                    'meta',

                'provider_message_id' =>
                    $providerMessageId,
            ]);

        return app(
            WhatsAppMessageService::class
        )->markSent(
            $message,
            'meta',
            $providerMessageId
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