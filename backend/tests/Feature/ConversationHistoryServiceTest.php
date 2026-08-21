<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Models\Conversation;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\ConversationHistoryService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ConversationHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_history_is_normalized(): void
    {
        $tenant = $this->tenant(
            'history-email'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'cliente@example.com'
        );

        $message = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversation->id,

                'to_email' =>
                    'cliente@example.com',

                'to_name' =>
                    'Cliente',

                'subject' =>
                    'Assunto',

                'body' =>
                    'Mensagem de e-mail',
            ]);

        $history = app(
            ConversationHistoryService::class
        )->history(
            $conversation
        );

        $this->assertCount(
            1,
            $history
        );

        $item = $history->first();

        $this->assertSame(
            'email',
            $item['channel']
        );

        $this->assertSame(
            'outbound',
            $item['direction']
        );

        $this->assertSame(
            'cliente@example.com',
            $item['address']
        );

        $this->assertSame(
            'Assunto',
            $item['subject']
        );

        $this->assertSame(
            'Mensagem de e-mail',
            $item['body']
        );

        $this->assertSame(
            $message->id,
            $item['source']->id
        );
    }

    public function test_whatsapp_history_is_normalized(): void
    {
        $tenant = $this->tenant(
            'history-whatsapp'
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

                'recipient_name' =>
                    'Cliente',

                'body' =>
                    'Mensagem WhatsApp',

                'direction' =>
                    'outbound',

                'provider' =>
                    'meta',

                'provider_message_id' =>
                    'wa-history-1',
            ]);

        $history = app(
            ConversationHistoryService::class
        )->history(
            $conversation
        );

        $this->assertCount(
            1,
            $history
        );

        $item = $history->first();

        $this->assertSame(
            'whatsapp',
            $item['channel']
        );

        $this->assertSame(
            'outbound',
            $item['direction']
        );

        $this->assertSame(
            'meta',
            $item['provider']
        );

        $this->assertSame(
            'wa-history-1',
            $item['provider_message_id']
        );

        $this->assertNull(
            $item['subject']
        );

        $this->assertSame(
            $message->id,
            $item['source']->id
        );
    }

    public function test_email_history_is_chronological(): void
    {
        $tenant = $this->tenant(
            'history-email-order'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'order@example.com'
        );

        $first = $this->email(
            $tenant,
            $conversation,
            'Primeiro',
            now()->subMinutes(10)
        );

        $second = $this->email(
            $tenant,
            $conversation,
            'Segundo',
            now()->subMinutes(5)
        );

        $third = $this->email(
            $tenant,
            $conversation,
            'Terceiro',
            now()
        );

        $history = app(
            ConversationHistoryService::class
        )->history(
            $conversation
        );

        $this->assertSame(
            [
                $first->id,
                $second->id,
                $third->id,
            ],
            $history
                ->pluck('id')
                ->all()
        );
    }

    public function test_whatsapp_history_contains_outbound_and_inbound(): void
    {
        $tenant = $this->tenant(
            'history-whatsapp-directions'
        );

        $conversation = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversation->id,

                'phone' =>
                    '5585999999999',

                'body' =>
                    'Saida',

                'direction' =>
                    'outbound',
            ]);

        WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversation->id,

                'phone' =>
                    '5585999999999',

                'body' =>
                    'Entrada',

                'direction' =>
                    'inbound',

                'status' =>
                    'received',

                'received_at' =>
                    now(),
            ]);

        $history = app(
            ConversationHistoryService::class
        )->history(
            $conversation
        );

        $this->assertCount(
            2,
            $history
        );

        $this->assertSame(
            [
                'outbound',
                'inbound',
            ],
            $history
                ->pluck('direction')
                ->all()
        );
    }

    public function test_email_history_does_not_include_other_conversation(): void
    {
        $tenant = $this->tenant(
            'history-email-isolation'
        );

        $conversationA = $this->conversation(
            ConversationChannel::EMAIL,
            'a@example.com'
        );

        $conversationB = $this->conversation(
            ConversationChannel::EMAIL,
            'b@example.com'
        );

        $expected = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversationA->id,

                'to_email' =>
                    'a@example.com',

                'subject' =>
                    'A',

                'body' =>
                    'Mensagem A',
            ]);

        EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversationB->id,

                'to_email' =>
                    'b@example.com',

                'subject' =>
                    'B',

                'body' =>
                    'Mensagem B',
            ]);

        $history = app(
            ConversationHistoryService::class
        )->history(
            $conversationA
        );

        $this->assertCount(
            1,
            $history
        );

        $this->assertSame(
            $expected->id,
            $history->first()['id']
        );
    }

    public function test_whatsapp_history_does_not_include_other_conversation(): void
    {
        $tenant = $this->tenant(
            'history-whatsapp-isolation'
        );

        $conversationA = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        $conversationB = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585888888888'
        );

        $expected = WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversationA->id,

                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem A',
            ]);

        WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversationB->id,

                'phone' =>
                    '5585888888888',

                'body' =>
                    'Mensagem B',
            ]);

        $history = app(
            ConversationHistoryService::class
        )->history(
            $conversationA
        );

        $this->assertCount(
            1,
            $history
        );

        $this->assertSame(
            $expected->id,
            $history->first()['id']
        );
    }

    public function test_history_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'history-tenant-a'
        );

        $conversationA = $this->conversation(
            ConversationChannel::EMAIL,
            'shared@example.com'
        );

        EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenantA->id,

                'conversation_id' =>
                    $conversationA->id,

                'to_email' =>
                    'shared@example.com',

                'subject' =>
                    'Tenant A',

                'body' =>
                    'Mensagem A',
            ]);

        $this->tenant(
            'history-tenant-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ConversationHistoryService::class
        )->history(
            $conversationA
        );
    }

    public function test_empty_conversation_has_empty_history(): void
    {
        $this->tenant(
            'history-empty'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'empty@example.com'
        );

        $history = app(
            ConversationHistoryService::class
        )->history(
            $conversation
        );

        $this->assertTrue(
            $history->isEmpty()
        );
    }

    private function email(
        Tenant $tenant,
        Conversation $conversation,
        string $subject,
        \DateTimeInterface $createdAt
    ): EmailMessage {
        $message = EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversation->id,

                'to_email' =>
                    $conversation->external_address,

                'subject' =>
                    $subject,

                'body' =>
                    'Mensagem ' . $subject,
            ]);

        $message->forceFill([
            'created_at' =>
                $createdAt,

            'updated_at' =>
                $createdAt,
        ])->save();

        return $message->refresh();
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