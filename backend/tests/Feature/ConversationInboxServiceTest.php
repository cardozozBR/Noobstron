<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\EmailMessage;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\ConversationInboxService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConversationInboxServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_is_ordered_by_last_message_desc(): void
    {
        $this->tenant(
            'inbox-order'
        );

        $old = $this->conversation(
            ConversationChannel::EMAIL,
            'old@example.com',
            [
                'last_message_at' =>
                    now()->subDay(),
            ]
        );

        $new = $this->conversation(
            ConversationChannel::EMAIL,
            'new@example.com',
            [
                'last_message_at' =>
                    now(),
            ]
        );

        $items = app(
            ConversationInboxService::class
        )->paginate()
            ->items();

        $this->assertSame(
            $new->id,
            $items[0]->id
        );

        $this->assertSame(
            $old->id,
            $items[1]->id
        );
    }

    public function test_conversation_without_messages_is_sorted_last(): void
    {
        $this->tenant(
            'inbox-null-last'
        );

        $withoutMessage = $this->conversation(
            ConversationChannel::EMAIL,
            'empty@example.com'
        );

        $withMessage = $this->conversation(
            ConversationChannel::EMAIL,
            'message@example.com',
            [
                'last_message_at' =>
                    now(),
            ]
        );

        $items = app(
            ConversationInboxService::class
        )->paginate()
            ->items();

        $this->assertSame(
            $withMessage->id,
            $items[0]->id
        );

        $this->assertSame(
            $withoutMessage->id,
            $items[1]->id
        );
    }

    public function test_inbox_can_search_display_name(): void
    {
        $this->tenant(
            'inbox-search-name'
        );

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'cliente@example.com',
            [
                'display_name' =>
                    'Maria Comercial',
            ]
        );

        $this->conversation(
            ConversationChannel::EMAIL,
            'outro@example.com',
            [
                'display_name' =>
                    'Joao',
            ]
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'search' =>
                'Maria',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_search_external_address(): void
    {
        $this->tenant(
            'inbox-search-address'
        );

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'target@example.com'
        );

        $this->conversation(
            ConversationChannel::EMAIL,
            'other@example.com'
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'search' =>
                'target@example.com',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_search_email_subject(): void
    {
        $tenant = $this->tenant(
            'inbox-search-email-subject'
        );

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'subject@example.com'
        );

        EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $expected->id,

                'to_email' =>
                    'subject@example.com',

                'subject' =>
                    'Proposta especial ABC',

                'body' =>
                    'Corpo comum',
            ]);

        $this->conversation(
            ConversationChannel::EMAIL,
            'other@example.com'
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'search' =>
                'especial ABC',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_search_email_body(): void
    {
        $tenant = $this->tenant(
            'inbox-search-email-body'
        );

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'body@example.com'
        );

        EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $expected->id,

                'to_email' =>
                    'body@example.com',

                'subject' =>
                    'Assunto',
                'body' =>
                    'conteudo-unico-email',
            ]);

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'search' =>
                'conteudo-unico-email',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_search_whatsapp_body(): void
    {
        $tenant = $this->tenant(
            'inbox-search-whatsapp'
        );

        $expected = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        WhatsAppMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $expected->id,

                'phone' =>
                    '5585999999999',

                'body' =>
                    'codigo-whatsapp-xyz',
            ]);

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'search' =>
                'codigo-whatsapp-xyz',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_filter_channel(): void
    {
        $this->tenant(
            'inbox-channel'
        );

        $email = $this->conversation(
            ConversationChannel::EMAIL,
            'email@example.com'
        );

        $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'channel' =>
                'email',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $email->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_filter_status(): void
    {
        $this->tenant(
            'inbox-status'
        );

        $pending = $this->conversation(
            ConversationChannel::EMAIL,
            'pending@example.com',
            [
                'status' =>
                    ConversationStatus::PENDING,
            ]
        );

        $this->conversation(
            ConversationChannel::EMAIL,
            'open@example.com'
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'status' =>
                'pending',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $pending->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_filter_responsible(): void
    {
        $tenant = $this->tenant(
            'inbox-responsible'
        );

        $userA = $this->user(
            $tenant,
            'responsible-a'
        );

        $userB = $this->user(
            $tenant,
            'responsible-b'
        );

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'assigned@example.com',
            [
                'responsible_user_id' =>
                    $userA->id,
            ]
        );

        $this->conversation(
            ConversationChannel::EMAIL,
            'other@example.com',
            [
                'responsible_user_id' =>
                    $userB->id,
            ]
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'responsible_user_id' =>
                $userA->id,
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_filter_unassigned(): void
    {
        $tenant = $this->tenant(
            'inbox-unassigned'
        );

        $user = $this->user(
            $tenant,
            'assigned'
        );

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'unassigned@example.com'
        );

        $this->conversation(
            ConversationChannel::EMAIL,
            'assigned@example.com',
            [
                'responsible_user_id' =>
                    $user->id,
            ]
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'responsible_user_id' =>
                'unassigned',
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_filter_lead(): void
    {
        $tenant = $this->tenant(
            'inbox-lead'
        );

        $lead = Lead::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Inbox Lead',

                'status' =>
                    'new',

                'source' =>
                    'manual',
            ]);

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'lead@example.com',
            [
                'lead_id' =>
                    $lead->id,
            ]
        );

        $this->conversation(
            ConversationChannel::EMAIL,
            'other@example.com'
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'lead_id' =>
                $lead->id,
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_can_filter_customer(): void
    {
        $tenant = $this->tenant(
            'inbox-customer'
        );

        $customer = Customer::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'type' =>
                    'company',

                'name' =>
                    'Inbox Customer',
            ]);

        $expected = $this->conversation(
            ConversationChannel::EMAIL,
            'customer@example.com',
            [
                'customer_id' =>
                    $customer->id,
            ]
        );

        $this->conversation(
            ConversationChannel::EMAIL,
            'other@example.com'
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'customer_id' =>
                $customer->id,
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_filters_can_be_combined(): void
    {
        $tenant = $this->tenant(
            'inbox-combined'
        );

        $user = $this->user(
            $tenant,
            'combined'
        );

        $expected = $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585999999999',
            [
                'display_name' =>
                    'Cliente Especial',

                'responsible_user_id' =>
                    $user->id,

                'status' =>
                    ConversationStatus::PENDING,
            ]
        );

        $this->conversation(
            ConversationChannel::WHATSAPP,
            '5585888888888',
            [
                'display_name' =>
                    'Cliente Outro',

                'responsible_user_id' =>
                    $user->id,

                'status' =>
                    ConversationStatus::OPEN,
            ]
        );

        $items = app(
            ConversationInboxService::class
        )->paginate([
            'search' =>
                'Especial',

            'channel' =>
                'whatsapp',

            'status' =>
                'pending',

            'responsible_user_id' =>
                $user->id,
        ])->items();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            $expected->id,
            $items[0]->id
        );
    }

    public function test_inbox_paginates(): void
    {
        $this->tenant(
            'inbox-pagination'
        );

        foreach (range(1, 5) as $index) {
            $this->conversation(
                ConversationChannel::EMAIL,
                "pagination-$index@example.com",
                [
                    'last_message_at' =>
                        now()->subMinutes(
                            $index
                        ),
                ]
            );
        }

        $page = app(
            ConversationInboxService::class
        )->paginate(
            [],
            2
        );

        $this->assertSame(
            2,
            $page->perPage()
        );

        $this->assertSame(
            5,
            $page->total()
        );

        $this->assertCount(
            2,
            $page->items()
        );
    }

    public function test_invalid_channel_is_rejected(): void
    {
        $this->tenant(
            'inbox-invalid-channel'
        );

        $this->expectException(
            ValidationException::class
        );

        app(
            ConversationInboxService::class
        )->paginate([
            'channel' =>
                'sms',
        ]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->tenant(
            'inbox-invalid-status'
        );

        $this->expectException(
            ValidationException::class
        );

        app(
            ConversationInboxService::class
        )->paginate([
            'status' =>
                'unknown',
        ]);
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        $this->tenant(
            'inbox-invalid-page'
        );

        $this->expectException(
            ValidationException::class
        );

        app(
            ConversationInboxService::class
        )->paginate(
            [],
            101
        );
    }

    public function test_inbox_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'inbox-tenant-a'
        );

        $conversationA = $this->conversation(
            ConversationChannel::EMAIL,
            'shared@example.com'
        );

        $tenantB = $this->tenant(
            'inbox-tenant-b'
        );

        $conversationB = $this->conversation(
            ConversationChannel::EMAIL,
            'shared@example.com'
        );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $itemsA = app(
            ConversationInboxService::class
        )->paginate()
            ->items();

        $this->assertCount(
            1,
            $itemsA
        );

        $this->assertSame(
            $conversationA->id,
            $itemsA[0]->id
        );

        app(
            TenantContext::class
        )->set(
            $tenantB
        );

        $itemsB = app(
            ConversationInboxService::class
        )->paginate()
            ->items();

        $this->assertCount(
            1,
            $itemsB
        );

        $this->assertSame(
            $conversationB->id,
            $itemsB[0]->id
        );
    }

    public function test_inbox_eager_loads_relations(): void
    {
        $tenant = $this->tenant(
            'inbox-relations'
        );

        $user = $this->user(
            $tenant,
            'relations'
        );

        $conversation = $this->conversation(
            ConversationChannel::EMAIL,
            'relations@example.com',
            [
                'responsible_user_id' =>
                    $user->id,
            ]
        );

        $item = app(
            ConversationInboxService::class
        )->paginate()
            ->items()[0];

        $this->assertSame(
            $conversation->id,
            $item->id
        );

        $this->assertTrue(
            $item->relationLoaded(
                'responsible'
            )
        );

        $this->assertTrue(
            $item->relationLoaded(
                'lead'
            )
        );

        $this->assertTrue(
            $item->relationLoaded(
                'customer'
            )
        );
    }

    private function conversation(
        ConversationChannel $channel,
        string $address,
        array $attributes = []
    ): Conversation {
        return Conversation::query()
            ->create(
                array_merge(
                    [
                        'channel' =>
                            $channel,

                        'external_address' =>
                            $address,
                    ],
                    $attributes
                )
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

    private function user(
        Tenant $tenant,
        string $suffix
    ): User {
        return User::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Inbox User',

                'email' =>
                    "inbox-$suffix@example.com",

                'password' =>
                    Hash::make(
                        'TesteSenha123'
                    ),

                'role' =>
                    'user',
            ]);
    }
}