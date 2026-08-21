<?php

namespace Tests\Feature;

use App\Enums\WhatsAppMessageStatus;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppMessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_message_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'whatsapp-model-create'
        );

        $message = $this->message();

        $this->assertSame(
            $tenant->id,
            $message->tenant_id
        );
    }

    public function test_whatsapp_message_defaults_to_pending_outbound(): void
    {
        $this->tenant(
            'whatsapp-model-default'
        );

        $message = $this->message();

        $this->assertSame(
            WhatsAppMessageStatus::PENDING,
            $message->status
        );

        $this->assertSame(
            'outbound',
            $message->direction
        );
    }

    public function test_whatsapp_message_has_expected_casts(): void
    {
        $this->tenant(
            'whatsapp-model-casts'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem',

                'sent_at' =>
                    now(),

                'delivered_at' =>
                    now(),

                'read_at' =>
                    now(),

                'received_at' =>
                    now(),

                'failed_at' =>
                    now(),
            ]);

        $this->assertInstanceOf(
            WhatsAppMessageStatus::class,
            $message->status
        );

        $this->assertNotNull(
            $message->sent_at
        );

        $this->assertNotNull(
            $message->delivered_at
        );

        $this->assertNotNull(
            $message->read_at
        );

        $this->assertNotNull(
            $message->received_at
        );

        $this->assertNotNull(
            $message->failed_at
        );
    }

    public function test_phone_is_normalized(): void
    {
        $this->tenant(
            'whatsapp-model-phone'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '+55 (85) 99999-9999',

                'body' =>
                    'Mensagem',
            ]);

        $this->assertSame(
            '5585999999999',
            $message->phone
        );
    }

    public function test_optional_text_fields_are_normalized(): void
    {
        $this->tenant(
            'whatsapp-model-normalize'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'recipient_name' =>
                    '  Cliente  ',

                'body' =>
                    '  Olá pelo WhatsApp  ',

                'provider' =>
                    '  META  ',

                'provider_message_id' =>
                    '  wamid.123  ',
            ]);

        $this->assertSame(
            'Cliente',
            $message->recipient_name
        );

        $this->assertSame(
            'Olá pelo WhatsApp',
            $message->body
        );

        $this->assertSame(
            'meta',
            $message->provider
        );

        $this->assertSame(
            'wamid.123',
            $message->provider_message_id
        );
    }

    public function test_blank_phone_is_rejected(): void
    {
        $this->tenant(
            'whatsapp-model-blank-phone'
        );

        $this->expectException(
            RuntimeException::class
        );

        WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '   ',

                'body' =>
                    'Mensagem',
            ]);
    }

    public function test_blank_body_is_rejected(): void
    {
        $this->tenant(
            'whatsapp-model-blank-body'
        );

        $this->expectException(
            RuntimeException::class
        );

        WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    '   ',
            ]);
    }

    public function test_whatsapp_queries_are_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'whatsapp-model-a'
        );

        $messageA = $this->message();

        $tenantB = $this->tenant(
            'whatsapp-model-b'
        );

        $messageB = $this->message(
            '5585888888888'
        );

        $this->assertSame(
            1,
            WhatsAppMessage::query()
                ->count()
        );

        $this->assertSame(
            $messageB->id,
            WhatsAppMessage::query()
                ->firstOrFail()
                ->id
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            $messageA->id,
            WhatsAppMessage::query()
                ->firstOrFail()
                ->id
        );
    }

    public function test_whatsapp_message_from_other_tenant_cannot_be_found(): void
    {
        $this->tenant(
            'whatsapp-model-other-a'
        );

        $message = $this->message();

        $this->tenant(
            'whatsapp-model-other-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        WhatsAppMessage::query()
            ->findOrFail(
                $message->id
            );
    }

    public function test_tenant_has_whatsapp_messages_relation(): void
    {
        $tenant = $this->tenant(
            'whatsapp-model-relation'
        );

        $this->message();
        $this->message(
            '5585777777777'
        );

        $this->assertCount(
            2,
            $tenant
                ->whatsAppMessages()
                ->get()
        );
    }

    private function message(
        string $phone = '5585999999999'
    ): WhatsAppMessage {
        return WhatsAppMessage::query()
            ->create([
                'phone' =>
                    $phone,

                'recipient_name' =>
                    'Cliente',

                'body' =>
                    'Olá pelo WhatsApp',
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