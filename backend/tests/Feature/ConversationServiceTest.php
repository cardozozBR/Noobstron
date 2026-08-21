<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ConversationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_can_be_created(): void
    {
        $tenant = $this->tenant(
            'conversation-service-create'
        );

        $conversation = app(
            ConversationService::class
        )->create([
            'channel' =>
                ConversationChannel::EMAIL,

            'external_address' =>
                'service@example.com',
        ]);

        $this->assertSame(
            $tenant->id,
            $conversation->tenant_id
        );
    }

    public function test_email_conversation_can_be_resolved_idempotently(): void
    {
        $this->tenant(
            'conversation-service-resolve-email'
        );

        $service = app(
            ConversationService::class
        );

        $first = $service->resolve(
            ConversationChannel::EMAIL,
            '  CLIENTE@EXAMPLE.COM  ',
            'Cliente'
        );

        $second = $service->resolve(
            ConversationChannel::EMAIL,
            'cliente@example.com'
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            'cliente@example.com',
            $first->external_address
        );
    }

    public function test_whatsapp_conversation_can_be_resolved_idempotently(): void
    {
        $this->tenant(
            'conversation-service-resolve-whatsapp'
        );

        $service = app(
            ConversationService::class
        );

        $first = $service->resolve(
            ConversationChannel::WHATSAPP,
            '+55 (85) 99999-9999'
        );

        $second = $service->resolve(
            ConversationChannel::WHATSAPP,
            '5585999999999'
        );

        $this->assertSame(
            $first->id,
            $second->id
        );
    }

    public function test_resolve_can_fill_missing_display_name(): void
    {
        $this->tenant(
            'conversation-display'
        );

        $service = app(
            ConversationService::class
        );

        $conversation = $service->resolve(
            ConversationChannel::EMAIL,
            'display@example.com'
        );

        $conversation = $service->resolve(
            ConversationChannel::EMAIL,
            'display@example.com',
            'Cliente Display'
        );

        $this->assertSame(
            'Cliente Display',
            $conversation->display_name
        );
    }

    public function test_conversation_can_be_assigned(): void
    {
        $tenant = $this->tenant(
            'conversation-assign'
        );

        $conversation = $this->conversation();

        $user = $this->user(
            $tenant,
            'assign'
        );

        $conversation = app(
            ConversationService::class
        )->assign(
            $conversation,
            $user
        );

        $this->assertSame(
            $user->id,
            $conversation->responsible_user_id
        );
    }

    public function test_conversation_can_be_unassigned(): void
    {
        $tenant = $this->tenant(
            'conversation-unassign'
        );

        $conversation = $this->conversation();

        $user = $this->user(
            $tenant,
            'unassign'
        );

        $service = app(
            ConversationService::class
        );

        $conversation = $service->assign(
            $conversation,
            $user
        );

        $conversation = $service->assign(
            $conversation,
            null
        );

        $this->assertNull(
            $conversation->responsible_user_id
        );
    }

    public function test_other_tenant_user_cannot_be_assigned(): void
    {
        $tenantA = $this->tenant(
            'conversation-assign-a'
        );

        $conversation = $this->conversation();

        $tenantB = $this->tenant(
            'conversation-assign-b'
        );

        $userB = $this->user(
            $tenantB,
            'assign-b'
        );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ConversationService::class
        )->assign(
            $conversation,
            $userB
        );
    }

    public function test_conversation_can_be_associated_to_lead(): void
    {
        $tenant = $this->tenant(
            'conversation-lead'
        );

        $conversation = $this->conversation();

        $lead = Lead::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Conversation Lead',

                'status' =>
                    'new',

                'source' =>
                    'manual',
            ]);

        $conversation = app(
            ConversationService::class
        )->associateLead(
            $conversation,
            $lead
        );

        $this->assertSame(
            $lead->id,
            $conversation->lead_id
        );

        $this->assertNull(
            $conversation->customer_id
        );
    }

    public function test_conversation_can_be_associated_to_customer(): void
    {
        $tenant = $this->tenant(
            'conversation-customer'
        );

        $conversation = $this->conversation();

        $customer = Customer::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'type' =>
                    'company',

                'name' =>
                    'Conversation Customer',
            ]);

        $conversation = app(
            ConversationService::class
        )->associateCustomer(
            $conversation,
            $customer
        );

        $this->assertSame(
            $customer->id,
            $conversation->customer_id
        );

        $this->assertNull(
            $conversation->lead_id
        );
    }

    public function test_customer_association_replaces_lead(): void
    {
        $tenant = $this->tenant(
            'conversation-replace-association'
        );

        $conversation = $this->conversation();

        $lead = Lead::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Conversation Lead',

                'status' =>
                    'new',

                'source' =>
                    'manual',
            ]);

        $customer = Customer::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'type' =>
                    'company',

                'name' =>
                    'Conversation Customer',
            ]);

        $service = app(
            ConversationService::class
        );

        $conversation = $service->associateLead(
            $conversation,
            $lead
        );

        $conversation = $service->associateCustomer(
            $conversation,
            $customer
        );

        $this->assertNull(
            $conversation->lead_id
        );

        $this->assertSame(
            $customer->id,
            $conversation->customer_id
        );
    }

    public function test_open_conversation_can_be_pending(): void
    {
        $this->tenant(
            'conversation-pending'
        );

        $conversation = app(
            ConversationService::class
        )->markPending(
            $this->conversation()
        );

        $this->assertSame(
            ConversationStatus::PENDING,
            $conversation->status
        );
    }

    public function test_pending_conversation_can_be_reopened(): void
    {
        $this->tenant(
            'conversation-reopen-pending'
        );

        $service = app(
            ConversationService::class
        );

        $conversation = $service->markPending(
            $this->conversation()
        );

        $conversation = $service->reopen(
            $conversation
        );

        $this->assertSame(
            ConversationStatus::OPEN,
            $conversation->status
        );
    }

    public function test_conversation_can_be_closed(): void
    {
        $this->tenant(
            'conversation-close'
        );

        $conversation = app(
            ConversationService::class
        )->close(
            $this->conversation()
        );

        $this->assertSame(
            ConversationStatus::CLOSED,
            $conversation->status
        );

        $this->assertNotNull(
            $conversation->closed_at
        );
    }

    public function test_closed_conversation_can_be_reopened(): void
    {
        $this->tenant(
            'conversation-reopen-closed'
        );

        $service = app(
            ConversationService::class
        );

        $conversation = $service->close(
            $this->conversation()
        );

        $conversation = $service->reopen(
            $conversation
        );

        $this->assertSame(
            ConversationStatus::OPEN,
            $conversation->status
        );

        $this->assertNull(
            $conversation->closed_at
        );
    }

    public function test_invalid_pending_transition_is_rejected(): void
    {
        $this->tenant(
            'conversation-invalid-pending'
        );

        $service = app(
            ConversationService::class
        );

        $conversation = $service->close(
            $this->conversation()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->markPending(
            $conversation
        );
    }

    public function test_last_message_time_can_be_updated(): void
    {
        $this->tenant(
            'conversation-last-message'
        );

        $conversation = app(
            ConversationService::class
        )->touchLastMessage(
            $this->conversation()
        );

        $this->assertNotNull(
            $conversation->last_message_at
        );
    }

    public function test_other_tenant_conversation_cannot_be_modified(): void
    {
        $tenantA = $this->tenant(
            'conversation-other-a'
        );

        $conversation = $this->conversation();

        $this->tenant(
            'conversation-other-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ConversationService::class
        )->close(
            $conversation
        );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    private function conversation(): Conversation
    {
        return Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    fake()->unique()->safeEmail(),
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

    private function user(
        Tenant $tenant,
        string $suffix
    ): User {
        return User::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Conversation User',

                'email' =>
                    "conversation-$suffix@example.com",

                'password' =>
                    Hash::make(
                        'TesteSenha123'
                    ),

                'role' =>
                    'user',
            ]);
    }
}