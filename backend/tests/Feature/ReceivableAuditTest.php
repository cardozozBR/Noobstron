<?php

namespace Tests\Feature;

use App\Enums\ReceivableStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ReceivableAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_receivable_creation_is_audited(): void
    {
        [
            $tenant,
            $customer,
            $user,
        ] = $this->environment(
            'receivable-audit-create'
        );

        $this->actingAs($user);

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Fatura auditada',
            'amount_minor' => 100000,
            'due_date' => '2026-09-30',
        ]);

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'receivable.created',
            ]
        );
    }

    public function test_receivable_update_is_audited(): void
    {
        [
            $tenant,
            $customer,
            $user,
        ] = $this->environment(
            'receivable-audit-update'
        );

        $this->actingAs($user);

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Original',
            'amount_minor' => 100000,
            'due_date' => '2026-09-30',
        ]);

        $service->update(
            $receivable,
            [
                'title' => 'Atualizada',
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'receivable.updated',
            ]
        );
    }

    public function test_receivable_payment_is_audited(): void
    {
        [
            $tenant,
            $customer,
            $user,
        ] = $this->environment(
            'receivable-audit-paid'
        );

        $this->actingAs($user);

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Pagamento',
            'amount_minor' => 200000,
            'due_date' => '2026-09-30',
        ]);

        $paid = $service->markPaid(
            $receivable,
            'PIX-AUDIT-001'
        );

        $this->assertSame(
            ReceivableStatus::PAID,
            $paid->status
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'receivable.paid',
            ]
        );
    }

    public function test_receivable_cancellation_is_audited(): void
    {
        [
            $tenant,
            $customer,
            $user,
        ] = $this->environment(
            'receivable-audit-cancel'
        );

        $this->actingAs($user);

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Cancelar',
            'amount_minor' => 300000,
            'due_date' => '2026-09-30',
        ]);

        $cancelled = $service->cancel(
            $receivable
        );

        $this->assertSame(
            ReceivableStatus::CANCELLED,
            $cancelled->status
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'receivable.cancelled',
            ]
        );
    }

    public function test_failed_payment_does_not_create_extra_audit(): void
    {
        [
            ,
            $customer,
            $user,
        ] = $this->environment(
            'receivable-audit-failed-payment'
        );

        $this->actingAs($user);

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Pago uma vez',
            'amount_minor' => 100000,
            'due_date' => '2026-09-30',
        ]);

        $service->markPaid(
            $receivable,
            'PIX-001'
        );

        $before = AuditLog::query()
            ->where(
                'action',
                'receivable.paid'
            )
            ->count();

        try {
            $service->markPaid(
                $receivable->refresh(),
                'PIX-002'
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
                'receivable.paid'
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

    public function test_receivable_audit_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $customerA,
            $userA,
        ] = $this->environment(
            'receivable-audit-a'
        );

        $this->actingAs($userA);

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customerA->id,
            'title' => 'Tenant A',
            'amount_minor' => 100000,
            'due_date' => '2026-09-30',
        ]);

        [
            $tenantB,
            $customerB,
            $userB,
        ] = $this->environment(
            'receivable-audit-b'
        );

        $this->actingAs($userB);

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customerB->id,
            'title' => 'Tenant B',
            'amount_minor' => 200000,
            'due_date' => '2026-09-30',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where(
                    'action',
                    'receivable.created'
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
                    'receivable.created'
                )
                ->count()
        );
    }

    private function environment(
        string $slug
    ): array {
        $tenant = Tenant::query()->create([
            'name' =>
                'Tenant ' . $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Receivable Auditor',
            'email' => $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' =>
                'Cliente ' . $slug,
        ]);

        return [
            $tenant,
            $customer,
            $user,
        ];
    }
}