<?php

namespace Tests\Feature;

use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantCapabilities;
use App\Services\TenantContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_email_routes_require_authentication(): void
    {
        $tenant = $this->tenantOnly(
            'email-http-auth'
        );

        $this->get(
            "http://{$tenant->slug}.localhost/email"
        )
            ->assertRedirect(
                "http://{$tenant->slug}.localhost/login"
            );
    }

    public function test_email_index_requires_feature(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-http-feature'
        );

        $this->permission(
            $user,
            'email.view'
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::EMAIL,
            false
        );

        $this->actingAs(
            $user
        );

        $this->get("http://{$tenant->slug}.localhost/email")
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_email_index(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-http-index'
        );

        $this->enableEmail(
            $tenant
        );

        $this->permission(
            $user,
            'email.view'
        );

        $this->actingAs(
            $user
        );

        $this->get("http://{$tenant->slug}.localhost/email")
            ->assertOk();
    }

    public function test_store_creates_email_message(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-http-store'
        );

        $this->enableEmail(
            $tenant
        );

        $this->permission(
            $user,
            'email.create'
        );

        $this->actingAs(
            $user
        );

        $this->post(
            "http://{$tenant->slug}.localhost/email",
            [
                'to_email' =>
                    'cliente@example.com',

                'to_name' =>
                    'Cliente',

                'subject' =>
                    'Olá Cliente',

                'body' =>
                    'Mensagem de relacionamento.',

                'action' =>
                    'save',
            ]
        )->assertRedirect(
            "http://{$tenant->slug}.localhost/email"
        );

        $this->assertDatabaseHas(
            'email_messages',
            [
                'tenant_id' =>
                    $tenant->id,

                'to_email' =>
                    'cliente@example.com',

                'subject' =>
                    'Olá Cliente',
            ]
        );
    }

    public function test_send_action_dispatches_queue(): void
    {
        Queue::fake();

        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-http-send'
        );

        $this->enableEmail(
            $tenant
        );

        $this->permission(
            $user,
            'email.send'
        );

        $message =
            EmailMessage::query()->create([
                'to_email' =>
                    'cliente@example.com',

                'subject' =>
                    'Mensagem',

                'body' =>
                    'Conteúdo',
            ]);

        $this->actingAs(
            $user
        );

        $this->post(
            "http://{$tenant->slug}.localhost/email/"
                . $message->id
                . '/send'
        )->assertRedirect(
            "http://{$tenant->slug}.localhost/email"
        );

        Queue::assertPushed(
            \App\Jobs\SendEmailMessageJob::class
        );
    }

    public function test_email_index_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $userA,
        ] = $this->environment(
            'email-http-a'
        );

        $this->enableEmail(
            $tenantA
        );

        $this->permission(
            $userA,
            'email.view'
        );

        EmailMessage::query()->create([
            'to_email' =>
                'a@example.com',

            'subject' =>
                'TENANT-A-SUBJECT',

            'body' =>
                'Mensagem A',
        ]);

        [
            $tenantB,
            $userB,
        ] = $this->environment(
            'email-http-b'
        );

        $this->enableEmail(
            $tenantB
        );

        $this->permission(
            $userB,
            'email.view'
        );

        EmailMessage::query()->create([
            'to_email' =>
                'b@example.com',

            'subject' =>
                'TENANT-B-SUBJECT',

            'body' =>
                'Mensagem B',
        ]);

        $this->actingAs(
            $userA
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->get("http://{$tenantA->slug}.localhost/email")
            ->assertOk()
            ->assertSee(
                'TENANT-A-SUBJECT'
            )
            ->assertDontSee(
                'TENANT-B-SUBJECT'
            );
    }

    private function tenantOnly(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
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
    private function environment(
        string $slug
    ): array {
        $tenant = Tenant::query()->create([
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

        $user = User::query()->create([
            'tenant_id' =>
                $tenant->id,

            'name' =>
                'Email User',

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

    private function enableEmail(
        Tenant $tenant
    ): void {
        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::EMAIL,
            true
        );
    }

    private function permission(
        User $user,
        string $name
    ): void {
        $id = DB::table(
            'permissions'
        )
            ->where(
                'name',
                $name
            )
            ->value(
                'id'
            );

        $user->permissions()
            ->syncWithoutDetaching([
                $id,
            ]);
    }
}