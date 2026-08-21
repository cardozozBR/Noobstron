<?php

namespace Tests\Feature;

use App\Enums\ConversationChannel;
use App\Enums\Feature;
use App\Models\Conversation;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InboxDetailUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_detail_renders_real_email_history(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-detail-history'
            );

        $this->allow(
            $tenant,
            $user,
            'inbox.view'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'cliente@example.com',

                'display_name' =>
                    'Cliente Exemplo',
            ]);

        EmailMessage::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'conversation_id' =>
                    $conversation->id,

                'to_email' =>
                    'cliente@example.com',

                'subject' =>
                    'Contato',

                'body' =>
                    'Mensagem real da conversa',
            ]);

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}"
        )
            ->assertOk()
            ->assertSee(
                'Cliente Exemplo'
            )
            ->assertSee(
                'Mensagem real da conversa'
            );
    }

    public function test_html_status_action_redirects_to_detail(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-html-status'
            );

        $this->allow(
            $tenant,
            $user,
            'inbox.manage'
        );

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'status@example.com',
            ]);

        $this->actingAs(
            $user
        );

        $this->put(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}/status",
            [
                'status' =>
                    'pending',
            ]
        )
            ->assertRedirect(
                route(
                    'inbox.show',
                    $conversation->id
                )
            );

        $this->assertSame(
            'pending',
            $conversation
                ->refresh()
                ->status
                ->value
        );
    }

    public function test_html_assignment_action_redirects_to_detail(): void
    {
        [$tenant, $user] =
            $this->environment(
                'inbox-html-assignment'
            );

        $this->allow(
            $tenant,
            $user,
            'inbox.assign'
        );

        $responsible = User::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Responsible User',

                'email' =>
                    'responsible@local',

                'password' =>
                    Hash::make(
                        'TesteSenha123'
                    ),

                'role' =>
                    'user',
            ]);

        $conversation = Conversation::query()
            ->create([
                'channel' =>
                    ConversationChannel::EMAIL,

                'external_address' =>
                    'assign@example.com',
            ]);

        $this->actingAs(
            $user
        );

        $this->put(
            "http://{$tenant->slug}.localhost/inbox/{$conversation->id}/assignment",
            [
                'responsible_user_id' =>
                    $responsible->id,
            ]
        )
            ->assertRedirect(
                route(
                    'inbox.show',
                    $conversation->id
                )
            );

        $this->assertSame(
            $responsible->id,
            $conversation
                ->refresh()
                ->responsible_user_id
        );
    }

    private function environment(
        string $slug
    ): array {
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

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::INBOX,
            true
        );

        $user = User::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Inbox Detail User',

                'email' =>
                    $slug . '@local',

                'password' =>
                    Hash::make(
                        'TesteSenha123'
                    ),

                'role' =>
                    'user',
            ]);

        return [
            $tenant,
            $user,
        ];
    }

    private function allow(
        Tenant $tenant,
        User $user,
        string $permission
    ): void {
        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::INBOX,
            true
        );

        $id = DB::table(
            'permissions'
        )
            ->where(
                'name',
                $permission
            )
            ->value(
                'id'
            );

        if ($id === null) {
            throw new \RuntimeException(
                'Permission not found: '
                . $permission
            );
        }

        $user->permissions()
            ->syncWithoutDetaching([
                $id,
            ]);
    }
}