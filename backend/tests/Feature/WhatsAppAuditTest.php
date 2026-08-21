<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class WhatsAppAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_creation_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'whatsapp-audit-create'
        );

        $this->actingAs(
            $user
        );

        app(
            WhatsAppMessageService::class
        )->create(
            $this->messageData()
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    $user->id,

                'action' =>
                    'whatsapp.created',
            ]
        );
    }

    public function test_whatsapp_sent_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'whatsapp-audit-sent'
        );

        $this->actingAs(
            $user
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $service->markSent(
            $message,
            'meta',
            'provider-sent-1'
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    $user->id,

                'action' =>
                    'whatsapp.sent',
            ]
        );
    }

    public function test_whatsapp_delivery_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'whatsapp-audit-delivered'
        );

        $this->actingAs(
            $user
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $message = $service->markSent(
            $message,
            'meta',
            'provider-delivered-1'
        );

        $service->markDelivered(
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
                    'whatsapp.delivered',
            ]
        );
    }

    public function test_whatsapp_read_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'whatsapp-audit-read'
        );

        $this->actingAs(
            $user
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $message = $service->markSent(
            $message,
            'meta',
            'provider-read-1'
        );

        $message = $service->markDelivered(
            $message
        );

        $service->markRead(
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
                    'whatsapp.read',
            ]
        );
    }

    public function test_whatsapp_failure_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'whatsapp-audit-failed'
        );

        $this->actingAs(
            $user
        );

        $service = app(
            WhatsAppMessageService::class
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
                    'whatsapp.failed',
            ]
        );
    }

    public function test_inbound_whatsapp_is_audited(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'whatsapp-audit-received'
        );

        $this->actingAs(
            $user
        );

        app(
            WhatsAppMessageService::class
        )->receive([
            'phone' =>
                '5585999999999',

            'body' =>
                'Mensagem recebida',

            'provider' =>
                'meta',

            'provider_message_id' =>
                'incoming-audit-1',
        ]);

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    $user->id,

                'action' =>
                    'whatsapp.received',
            ]
        );
    }

    public function test_invalid_sent_transition_does_not_create_extra_audit(): void
    {
        $this->environment(
            'whatsapp-audit-invalid-sent'
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $message = $service->markSent(
            $message,
            'meta',
            'invalid-sent-1'
        );

        $before = AuditLog::query()
            ->where(
                'action',
                'whatsapp.sent'
            )
            ->count();

        try {
            $service->markSent(
                $message,
                'meta',
                'invalid-sent-2'
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
                'whatsapp.sent'
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
            'whatsapp-audit-invalid-failure'
        );

        $service = app(
            WhatsAppMessageService::class
        );

        $message = $service->create(
            $this->messageData()
        );

        $before = AuditLog::query()
            ->where(
                'action',
                'whatsapp.failed'
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
                'whatsapp.failed'
            )
            ->count();

        $this->assertSame(
            $before,
            $after
        );
    }

    public function test_whatsapp_audit_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $userA,
        ] = $this->environment(
            'whatsapp-audit-a'
        );

        $this->actingAs(
            $userA
        );

        app(
            WhatsAppMessageService::class
        )->create(
            $this->messageData()
        );

        [
            $tenantB,
            $userB,
        ] = $this->environment(
            'whatsapp-audit-b'
        );

        $this->actingAs(
            $userB
        );

        app(
            WhatsAppMessageService::class
        )->create(
            $this->messageData()
        );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $logs = AuditLog::query()
            ->where(
                'action',
                'whatsapp.created'
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
            'phone' =>
                '5585999999999',

            'recipient_name' =>
                'Cliente',

            'body' =>
                'Mensagem comercial via WhatsApp.',
        ];
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

        $user = User::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'WhatsApp Audit User',

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