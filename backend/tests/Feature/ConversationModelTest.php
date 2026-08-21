<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ConversationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'conversation-create'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'cliente@example.com',

                'display_name' =>
                    'Cliente',
            ]);

        $this->assertSame(
            $tenant->id,
            $conversation->tenant_id
        );
    }

    public function test_conversation_defaults_to_open(): void
    {
        $this->tenant(
            'conversation-open'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'open@example.com',
            ]);

        $this->assertSame(
            ConversationStatus::OPEN,
            $conversation->status
        );
    }

    public function test_conversation_has_expected_casts(): void
    {
        $this->tenant(
            'conversation-casts'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::WHATSAPP,

                'external_address' =>
                    '+55 (85) 99999-9999',
            ]);

        $this->assertSame(
            ConversationChannel::WHATSAPP,
            $conversation->channel
        );

        $this->assertSame(
            ConversationStatus::OPEN,
            $conversation->status
        );
    }

    public function test_email_address_is_normalized(): void
    {
        $this->tenant(
            'conversation-email-normalize'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    '  CLIENTE@EXAMPLE.COM  ',
            ]);

        $this->assertSame(
            'cliente@example.com',
            $conversation->external_address
        );
    }

    public function test_whatsapp_phone_is_normalized(): void
    {
        $this->tenant(
            'conversation-phone-normalize'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::WHATSAPP,

                'external_address' =>
                    '+55 (85) 99999-9999',
            ]);

        $this->assertSame(
            '5585999999999',
            $conversation->external_address
        );
    }

    public function test_blank_external_address_is_rejected(): void
    {
        $this->tenant(
            'conversation-blank'
        );

        $this->expectException(
            RuntimeException::class
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    '   ',
            ]);
    }

    public function test_invalid_email_address_is_rejected(): void
    {
        $this->tenant(
            'conversation-invalid-email'
        );

        $this->expectException(
            RuntimeException::class
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'email-invalido',
            ]);
    }

    public function test_display_name_is_normalized(): void
    {
        $this->tenant(
            'conversation-display-name'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'name@example.com',

                'display_name' =>
                    '  Cliente Teste  ',
            ]);

        $this->assertSame(
            'Cliente Teste',
            $conversation->display_name
        );
    }

    public function test_blank_display_name_becomes_null(): void
    {
        $this->tenant(
            'conversation-null-name'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'null-name@example.com',

                'display_name' =>
                    '   ',
            ]);

        $this->assertNull(
            $conversation->display_name
        );
    }

    public function test_same_address_cannot_repeat_in_same_channel_and_tenant(): void
    {
        $this->tenant(
            'conversation-unique'
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'unique@example.com',
            ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'unique@example.com',
            ]);
    }

    public function test_same_address_can_exist_in_different_tenants(): void
    {
        $tenantA = $this->tenant(
            'conversation-tenant-a'
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'shared@example.com',
            ]);

        $tenantB = $this->tenant(
            'conversation-tenant-b'
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'shared@example.com',
            ]);

        $this->assertDatabaseHas(
            'conversations',
            [
                'tenant_id' =>
                    $tenantA->id,

                'external_address' =>
                    'shared@example.com',
            ]
        );

        $this->assertDatabaseHas(
            'conversations',
            [
                'tenant_id' =>
                    $tenantB->id,

                'external_address' =>
                    'shared@example.com',
            ]
        );
    }

    public function test_queries_are_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'conversation-query-a'
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'tenant-a@example.com',
            ]);

        $tenantB = $this->tenant(
            'conversation-query-b'
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'tenant-b@example.com',
            ]);

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $conversations = Conversation::query()
            ->get();

        $this->assertCount(
            1,
            $conversations
        );

        $this->assertSame(
            'tenant-a@example.com',
            $conversations->first()->external_address
        );

        app(
            TenantContext::class
        )->set(
            $tenantB
        );

        $this->assertCount(
            1,
            Conversation::query()->get()
        );
    }

    public function test_tenant_has_conversations_relation(): void
    {
        $tenant = $this->tenant(
            'conversation-relation'
        );

        Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::WHATSAPP,

                'external_address' =>
                    '5585999999999',
            ]);

        $this->assertCount(
            1,
            $tenant->conversations
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