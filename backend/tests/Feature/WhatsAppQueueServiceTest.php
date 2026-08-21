<?php

namespace Tests\Feature;

use App\Enums\WhatsAppMessageStatus;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use App\Services\WhatsAppQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class WhatsAppQueueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_message_can_be_dispatched(): void
    {
        Queue::fake();

        $tenant = $this->tenant(
            'queue-dispatch'
        );

        $message = $this->message();

        app(
            WhatsAppQueueService::class
        )->dispatch(
            $message,
            ' META '
        );

        Queue::assertPushed(
            SendWhatsAppMessageJob::class,
            function (
                SendWhatsAppMessageJob $job
            ) use (
                $tenant,
                $message
            ): bool {
                return $job->tenantId === $tenant->id
                    && $job->messageId === $message->id
                    && $job->provider === 'meta';
            }
        );
    }

    public function test_non_pending_message_cannot_be_dispatched(): void
    {
        Queue::fake();

        $this->tenant(
            'queue-non-pending'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem',

                'status' =>
                    WhatsAppMessageStatus::RECEIVED,

                'direction' =>
                    'inbound',

                'received_at' =>
                    now(),
            ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            WhatsAppQueueService::class
        )->dispatch(
            $message,
            'meta'
        );
    }

    public function test_provider_is_required(): void
    {
        Queue::fake();

        $this->tenant(
            'queue-provider-required'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            WhatsAppQueueService::class
        )->dispatch(
            $this->message(),
            '   '
        );
    }

    public function test_other_tenant_message_cannot_be_dispatched(): void
    {
        Queue::fake();

        $this->tenant(
            'queue-tenant-a'
        );

        $message = $this->message();

        $this->tenant(
            'queue-tenant-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            WhatsAppQueueService::class
        )->dispatch(
            $message,
            'meta'
        );
    }

    private function message(): WhatsAppMessage
    {
        return WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem WhatsApp',
            ]);
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