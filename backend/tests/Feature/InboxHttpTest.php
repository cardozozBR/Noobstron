<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Conversation;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InboxHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_index_requires_inbox_feature(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-feature'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_VIEW
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::INBOX,
            false
        );

        $this->actingAs(
            $user
        );

        $this->getJson(
            "http://{$tenant->slug}.localhost/inbox"
        )->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-permission'
            );

        $this->actingAs(
            $user
        );

        $this->getJson(
            "http://{$tenant->slug}.localhost/inbox"
        )->assertForbidden();
    }

    public function test_user_can_list_inbox(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-index'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_VIEW
        );

        $conversation = $this->conversation(
            'cliente@example.com'
        );

        $this->actingAs(
            $user
        );

        $this->getJson(
            "http://{$tenant->slug}.localhost/inbox"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $conversation->id
            );
    }

    public function test_user_can_show_conversation(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-show'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_VIEW
        );

        $conversation = $this->conversation(
            'show@example.com'
        );

        $this->actingAs(
            $user
        );

        $this->getJson(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'conversation.id',
                $conversation->id
            )
            ->assertJsonCount(
                0,
                'history'
            );
    }

    public function test_assign_requires_assign_permission(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-assign-permission'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_VIEW
        );

        $conversation = $this->conversation(
            'assign-permission@example.com'
        );

        $this->actingAs(
            $user
        );

        $this->putJson(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}/assignment",
            [
                'responsible_user_id' =>
                    $user->id,
            ]
        )->assertForbidden();
    }

    public function test_user_can_assign_conversation(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-assign'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_ASSIGN
        );

        $responsible = $this->user(
            $tenant,
            'responsible'
        );

        $conversation = $this->conversation(
            'assign@example.com'
        );

        $this->actingAs(
            $user
        );

        $this->putJson(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}/assignment",
            [
                'responsible_user_id' =>
                    $responsible->id,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'conversation.responsible_user_id',
                $responsible->id
            );

        $this->assertSame(
            $responsible->id,
            $conversation
                ->refresh()
                ->responsible_user_id
        );
    }

    public function test_manage_permission_can_set_pending(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-pending'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_MANAGE
        );

        $conversation = $this->conversation(
            'pending@example.com'
        );

        $this->actingAs(
            $user
        );

        $this->putJson(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}/status",
            [
                'status' =>
                    'pending',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'conversation.status',
                'pending'
            );

        $this->assertSame(
            ConversationStatus::PENDING,
            $conversation
                ->refresh()
                ->status
        );
    }

    public function test_manage_permission_can_close_and_reopen(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-status'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_MANAGE
        );

        $conversation = $this->conversation(
            'status@example.com'
        );

        $this->actingAs(
            $user
        );

        $url =
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}/status";

        $this->putJson(
            $url,
            [
                'status' =>
                    'closed',
            ]
        )->assertOk();

        $this->assertSame(
            ConversationStatus::CLOSED,
            $conversation
                ->refresh()
                ->status
        );

        $this->putJson(
            $url,
            [
                'status' =>
                    'open',
            ]
        )->assertOk();

        $this->assertSame(
            ConversationStatus::OPEN,
            $conversation
                ->refresh()
                ->status
        );
    }

    public function test_invalid_status_is_rejected(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-http-invalid-status'
            );

        $this->grant(
            $user,
            PermissionEnum::INBOX_MANAGE
        );

        $conversation = $this->conversation(
            'invalid-status@example.com'
        );

        $this->actingAs(
            $user
        );

        $this->putJson(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}/status",
            [
                'status' =>
                    'invalid',
            ]
        )->assertUnprocessable();
    }

    public function test_other_tenant_conversation_is_not_visible(): void
    {
        [$tenantA, $userA] =
            $this->environment(
                'inbox-http-tenant-a'
            );

        $this->grant(
            $userA,
            PermissionEnum::INBOX_VIEW
        );

        $conversationA = $this->conversation(
            'tenant-a@example.com'
        );

        [$tenantB, $userB] =
            $this->environment(
                'inbox-http-tenant-b'
            );

        $this->grant(
            $userB,
            PermissionEnum::INBOX_VIEW
        );

        $this->actingAs(
            $userB
        );

        $this->getJson(
            "http://{$tenantB->slug}.localhost/inbox/{$conversationA->id}"
        )->assertNotFound();

        $this->assertNotSame(
            $tenantA->id,
            $tenantB->id
        );
    }

    private function environment(
        string $slug
    ): array {
        $tenant = $this->tenant(
            $slug
        );

        app(
            \App\Services\TenantContext::class
        )->set(
            $tenant
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::INBOX,
            true
        );

        $user = $this->user(
            $tenant,
            $slug
        );

        return [
            $tenant,
            $user,
        ];
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where(
                'name',
                $permission->value
            )
            ->firstOrFail();

        $user
            ->permissions()
            ->syncWithoutDetaching([
                $model->id,
            ]);
    }

    private function conversation(
        string $address
    ): Conversation {
        return Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    $address,
            ]);
    }

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::query()
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