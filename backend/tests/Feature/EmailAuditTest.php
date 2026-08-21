<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailMessageService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class EmailAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_creation_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-audit-create'
        );

        $this->actingAs(
            $user
        );

        app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'cliente@example.com',

            'subject' =>
                'Mensagem',

            'body' =>
                'Conteúdo',
        ]);

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    $user->id,

                'action' =>
                    'email.created',
            ]
        );
    }

    public function test_email_sent_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-audit-sent'
        );

        $this->actingAs(
            $user
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $service->markSent(
            $message
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    $user->id,

                'action' =>
                    'email.sent',
            ]
        );
    }

    public function test_email_failure_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-audit-failed'
        );

        $this->actingAs(
            $user
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $service->markFailed(
            $message,
            'Provider unavailable'
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    $user->id,

                'action' =>
                    'email.failed',
            ]
        );
    }

    public function test_email_retry_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-audit-retry'
        );

        $this->actingAs(
            $user
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $message = $service->markFailed(
            $message,
            'Temporary failure'
        );

        $service->retry(
            $message
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    $user->id,

                'action' =>
                    'email.retried',
            ]
        );
    }

    public function test_invalid_sent_transition_does_not_create_extra_audit(): void
    {
        $this->environment(
            'email-audit-invalid-sent'
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $message = $service->markSent(
            $message
        );

        $before = AuditLog::query()
            ->where(
                'action',
                'email.sent'
            )
            ->count();

        try {
            $service->markSent(
                $message
            );

            $this->fail(
                'Expected RuntimeException.'
            );
        } catch (RuntimeException) {
            //
        }

        $after = AuditLog::query()
            ->where(
                'action',
                'email.sent'
            )
            ->count();

        $this->assertSame(
            $before,
            $after
        );

        $this->assertSame(
            1,
            $after
        );
    }

    public function test_invalid_failure_does_not_create_audit(): void
    {
        $this->environment(
            'email-audit-invalid-failure'
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $before = AuditLog::query()
            ->where(
                'action',
                'email.failed'
            )
            ->count();

        try {
            $service->markFailed(
                $message,
                '   '
            );

            $this->fail(
                'Expected RuntimeException.'
            );
        } catch (RuntimeException) {
            //
        }

        $after = AuditLog::query()
            ->where(
                'action',
                'email.failed'
            )
            ->count();

        $this->assertSame(
            $before,
            $after
        );
    }

    public function test_email_audit_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $userA,
        ] = $this->environment(
            'email-audit-a'
        );

        $this->actingAs(
            $userA
        );

        app(
            EmailMessageService::class
        )->create(
            $this->messageData()
        );

        [
            $tenantB,
            $userB,
        ] = $this->environment(
            'email-audit-b'
        );

        $this->actingAs(
            $userB
        );

        app(
            EmailMessageService::class
        )->create(
            $this->messageData()
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $logs = AuditLog::query()
            ->where(
                'action',
                'email.created'
            )
            ->get();

        $this->assertCount(
            1,
            $logs
        );

        $this->assertSame(
            $tenantA->id,
            $logs->first()->tenant_id
        );

        $this->assertNotSame(
            $tenantB->id,
            $logs->first()->tenant_id
        );
    }

    private function messageData(): array
    {
        return [
            'to_email' =>
                'cliente@example.com',

            'to_name' =>
                'Cliente',

            'subject' =>
                'Mensagem comercial',

            'body' =>
                'Conteúdo da mensagem.',
        ];
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

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::query()->create([
            'tenant_id' =>
                $tenant->id,

            'name' =>
                'Email Audit User',

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
}