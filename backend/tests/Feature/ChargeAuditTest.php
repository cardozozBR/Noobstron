<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ChargeService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ChargeAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_creation_is_audited(): void
    {
        [
            $tenant,
            $receivable,
            $user,
        ] = $this->environment(
            'charge-audit-create'
        );

        $this->actingAs($user);

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'charge.created',
            ]
        );
    }

    public function test_charge_sent_is_audited(): void
    {
        [
            $tenant,
            $receivable,
            $user,
        ] = $this->environment(
            'charge-audit-sent'
        );

        $this->actingAs($user);

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $service->markSent(
            $charge,
            'EXT-AUDIT-001'
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'charge.sent',
            ]
        );
    }

    public function test_charge_failure_is_audited(): void
    {
        [
            $tenant,
            $receivable,
            $user,
        ] = $this->environment(
            'charge-audit-failed'
        );

        $this->actingAs($user);

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $service->markFailed(
            $charge,
            'Provider unavailable'
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'charge.failed',
            ]
        );
    }

    public function test_charge_cancellation_is_audited(): void
    {
        [
            $tenant,
            $receivable,
            $user,
        ] = $this->environment(
            'charge-audit-cancel'
        );

        $this->actingAs($user);

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $service->cancel(
            $charge
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'charge.cancelled',
            ]
        );
    }

    public function test_invalid_send_does_not_create_extra_audit(): void
    {
        [
            ,
            $receivable,
            $user,
        ] = $this->environment(
            'charge-audit-invalid-send'
        );

        $this->actingAs($user);

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $service->markSent(
            $charge
        );

        $before = AuditLog::query()
            ->where(
                'action',
                'charge.sent'
            )
            ->count();

        try {
            $service->markSent(
                $charge->refresh()
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException) {
            //
        }

        $after = AuditLog::query()
            ->where(
                'action',
                'charge.sent'
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
        [
            ,
            $receivable,
            $user,
        ] = $this->environment(
            'charge-audit-invalid-failure'
        );

        $this->actingAs($user);

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $before = AuditLog::query()
            ->where(
                'action',
                'charge.failed'
            )
            ->count();

        try {
            $service->markFailed(
                $charge,
                '   '
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException) {
            //
        }

        $after = AuditLog::query()
            ->where(
                'action',
                'charge.failed'
            )
            ->count();

        $this->assertSame(
            $before,
            $after
        );

        $this->assertSame(
            0,
            $after
        );
    }

    public function test_charge_audit_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $receivableA,
            $userA,
        ] = $this->environment(
            'charge-audit-a'
        );

        $this->actingAs($userA);

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableA->id,
        ]);

        [
            $tenantB,
            $receivableB,
            $userB,
        ] = $this->environment(
            'charge-audit-b'
        );

        $this->actingAs($userB);

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableB->id,
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where(
                    'action',
                    'charge.created'
                )
                ->count()
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where(
                    'action',
                    'charge.created'
                )
                ->count()
        );
    }

    private function environment(
        string $slug
    ): array {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant ' . $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Charge Auditor',
            'email' => $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente ' . $slug,
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Titulo ' . $slug,
            'amount_minor' => 100000,
            'due_date' => '2026-10-31',
        ]);

        return [
            $tenant,
            $receivable,
            $user,
        ];
    }
}