<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Models\Conversation;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\ConversationMessageService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ConversationMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_message_can_be_attached_to_email_conversation(): void
    {
        $tenant = $this->tenant(
            'conversation-email-link'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'cliente@example.com'
        );

        $message = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'to_email' =>
                    'cliente@example.com',

                'subject' =>
                    'Assunto',

                'body' =>
                    'Mensagem de e-mail',
            ]);

        $message = app(
            ConversationMessageService::class
        )->attachEmail(
            $conversation,
            $message
        );

        $this->assertSame(
            $conversation->id,
            $message->conversation_id
        );

        $this->assertNotNull(
            $conversation->refresh()->last_message_at
        );
    }

    public function test_whatsapp_message_can_be_attached_to_whatsapp_conversation(): void
    {
        $tenant = $this->tenant(
            'conversation-whatsapp-link'
        );

        $conversation = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'phone' =>
                    '+55 (85) 99999-9999',

                'body' =>
                    'Mensagem WhatsApp',
            ]);

        $message = app(
            ConversationMessageService::class
        )->attachWhatsApp(
            $conversation,
            $message
        );

        $this->assertSame(
            $conversation->id,
            $message->conversation_id
        );

        $this->assertNotNull(
            $conversation->refresh()->last_message_at
        );
    }

    public function test_email_message_requires_email_conversation(): void
    {
        $tenant = $this->tenant(
            'conversation-email-channel'
        );

        $conversation = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        $message = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'to_email' =>
                    'cliente@example.com',

                'subject' =>
                    'Assunto',

                'body' =>
                    'Mensagem',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            ConversationMessageService::class
        )->attachEmail(
            $conversation,
            $message
        );
    }

    public function test_email_recipient_must_match_conversation(): void
    {
        $tenant = $this->tenant(
            'conversation-email-address'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'cliente@example.com'
        );

        $message = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'to_email' =>
                    'outro@example.com',

                'subject' =>
                    'Assunto',

                'body' =>
                    'Mensagem',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            ConversationMessageService::class
        )->attachEmail(
            $conversation,
            $message
        );
    }

    public function test_whatsapp_phone_must_match_conversation(): void
    {
        $tenant = $this->tenant(
            'conversation-whatsapp-address'
        );

        $conversation = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'phone' =>
                    '5585888888888',

                'body' =>
                    'Mensagem',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            ConversationMessageService::class
        )->attachWhatsApp(
            $conversation,
            $message
        );
    }

    public function test_email_relation_is_available_from_conversation(): void
    {
        $tenant = $this->tenant(
            'conversation-email-relation'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'relation@example.com'
        );

        $message = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversation->id,

                'to_email' =>
                    'relation@example.com',

                'subject' =>
                    'Assunto',

                'body' =>
                    'Mensagem',
            ]);

        $this->assertSame(
            $message->id,
            $conversation
                ->emailMessages
                ->first()
                ->id
        );

        $this->assertSame(
            $conversation->id,
            $message
                ->conversation
                ->id
        );
    }

    public function test_whatsapp_relation_is_available_from_conversation(): void
    {
        $tenant = $this->tenant(
            'conversation-whatsapp-relation'
        );

        $conversation = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversation->id,

                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem',
            ]);

        $this->assertSame(
            $message->id,
            $conversation
                ->whatsappMessages
                ->first()
                ->id
        );

        $this->assertSame(
            $conversation->id,
            $message
                ->conversation
                ->id
        );
    }

    public function test_message_from_other_tenant_cannot_be_attached(): void
    {
        $tenantA = $this->tenant(
            'conversation-link-a'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'shared@example.com'
        );

        $tenantB = $this->tenant(
            'conversation-link-b'
        );

        $message = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenantB->id,

                'to_email' =>
                    'shared@example.com',

                'subject' =>
                    'Assunto',

                'body' =>
                    'Mensagem',
            ]);

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ConversationMessageService::class
        )->attachEmail(
            $conversation,
            $message
        );
    }

    private function conversation(
        ConversationChannel $channel,
        string $address
    ): Conversation {
        return Conversation::query()
            ->create([
                'channel' =>
                    $channel,

                'external_address' =>
                    $address,
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