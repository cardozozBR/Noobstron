<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Services\EmailMessageService;
use App\Services\TenantContext;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationAutoLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_email_is_automatically_linked(): void
    {
        $this->tenant(
            'auto-link-email'
        );

        $message = app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'cliente@example.com',

            'to_name' =>
                'Cliente',

            'subject' =>
                'Assunto',

            'body' =>
                'Mensagem',
        ]);

        $this->assertNotNull(
            $message->conversation_id
        );

        $conversation = Conversation::query()
            ->findOrFail(
                $message->conversation_id
            );

        $this->assertSame(
            ConversationChannel::EMAIL,
            $conversation->channel
        );

        $this->assertSame(
            'cliente@example.com',
            $conversation->external_address
        );
    }

    public function test_repeated_email_recipient_reuses_conversation(): void
    {
        $this->tenant(
            'auto-link-email-reuse'
        );

        $service = app(
            EmailMessageService::class
        );

        $first = $service->create([
            'to_email' =>
                'cliente@example.com',

            'subject' =>
                'Primeiro',

            'body' =>
                'Mensagem 1',
        ]);

        $second = $service->create([
            'to_email' =>
                'CLIENTE@example.com',

            'subject' =>
                'Segundo',

            'body' =>
                'Mensagem 2',
        ]);

        $this->assertSame(
            $first->conversation_id,
            $second->conversation_id
        );

        $this->assertSame(
            1,
            Conversation::query()->count()
        );
    }

    public function test_created_whatsapp_is_automatically_linked(): void
    {
        $this->tenant(
            'auto-link-whatsapp'
        );

        $message = app(
            WhatsAppMessageService::class
        )->create([
            'phone' =>
                '+55 (85) 99999-9999',

            'recipient_name' =>
                'Cliente',

            'body' =>
                'Mensagem',
        ]);

        $this->assertNotNull(
            $message->conversation_id
        );

        $conversation = Conversation::query()
            ->findOrFail(
                $message->conversation_id
            );

        $this->assertSame(
            ConversationChannel::WHATSAPP,
            $conversation->channel
        );

        $this->assertSame(
            '5585999999999',
            $conversation->external_address
        );
    }

    public function test_repeated_whatsapp_phone_reuses_conversation(): void
    {
        $this->tenant(
            'auto-link-whatsapp-reuse'
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $first = $service->create([
            'phone' =>
                '+55 (85) 99999-9999',

            'body' =>
                'Mensagem 1',
        ]);

        $second = $service->create([
            'phone' =>
                '5585999999999',

            'body' =>
                'Mensagem 2',
        ]);

        $this->assertSame(
            $first->conversation_id,
            $second->conversation_id
        );

        $this->assertSame(
            1,
            Conversation::query()->count()
        );
    }

    public function test_received_whatsapp_is_automatically_linked(): void
    {
        $this->tenant(
            'auto-link-whatsapp-received'
        );

        $message = app(
            WhatsAppMessageService::class
        )->receive([
            'phone' =>
                '5585999999999',

            'recipient_name' =>
                'Cliente',

            'body' =>
                'Mensagem recebida',

            'provider' =>
                'meta',

            'provider_message_id' =>
                'incoming-auto-link-1',
        ]);

        $this->assertNotNull(
            $message->conversation_id
        );

        $conversation = Conversation::query()
            ->findOrFail(
                $message->conversation_id
            );

        $this->assertSame(
            ConversationChannel::WHATSAPP,
            $conversation->channel
        );
    }

    public function test_outbound_and_inbound_whatsapp_share_conversation(): void
    {
        $this->tenant(
            'auto-link-whatsapp-both'
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $outbound = $service->create([
            'phone' =>
                '5585999999999',

            'body' =>
                'Mensagem enviada',
        ]);

        $inbound = $service->receive([
            'phone' =>
                '+55 85 99999-9999',

            'body' =>
                'Mensagem recebida',

            'provider' =>
                'meta',

            'provider_message_id' =>
                'incoming-auto-link-2',
        ]);

        $this->assertSame(
            $outbound->conversation_id,
            $inbound->conversation_id
        );

        $this->assertSame(
            1,
            Conversation::query()->count()
        );
    }

    public function test_same_address_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'auto-link-a'
        );

        $first = app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'shared@example.com',

            'subject' =>
                'A',

            'body' =>
                'Mensagem A',
        ]);

        $tenantB = $this->tenant(
            'auto-link-b'
        );

        $second = app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'shared@example.com',

            'subject' =>
                'B',

            'body' =>
                'Mensagem B',
        ]);

        $this->assertNotSame(
            $first->conversation_id,
            $second->conversation_id
        );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $this->assertSame(
            1,
            Conversation::query()->count()
        );

        app(
            TenantContext::class
        )->set(
            $tenantB
        );

        $this->assertSame(
            1,
            Conversation::query()->count()
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