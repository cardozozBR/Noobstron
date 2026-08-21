<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\ReceivableStatus;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Services\ChargeService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ChargeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_can_be_created(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-create'
            );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
            'channel' => 'email',
            'recipient' =>
                'billing@example.com',
        ]);

        $this->assertSame(
            ChargeStatus::PENDING,
            $charge->status
        );

        $this->assertSame(
            1,
            $charge->attempt
        );

        $this->assertSame(
            'email',
            $charge->channel
        );
    }

    public function test_attempt_is_incremented_for_same_receivable(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-attempt'
            );

        $service = app(
            ChargeService::class
        );

        $first = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $second = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $this->assertSame(
            1,
            $first->attempt
        );

        $this->assertSame(
            2,
            $second->attempt
        );
    }

    public function test_charge_can_be_marked_as_sent(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-sent'
            );

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $sent = $service->markSent(
            $charge,
            'EXT-001'
        );

        $this->assertSame(
            ChargeStatus::SENT,
            $sent->status
        );

        $this->assertNotNull(
            $sent->sent_at
        );

        $this->assertSame(
            'EXT-001',
            $sent->external_reference
        );
    }

    public function test_charge_can_be_marked_as_failed(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-failed'
            );

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $failed = $service->markFailed(
            $charge,
            'Transport unavailable'
        );

        $this->assertSame(
            ChargeStatus::FAILED,
            $failed->status
        );

        $this->assertNotNull(
            $failed->failed_at
        );

        $this->assertSame(
            'Transport unavailable',
            $failed->failure_reason
        );
    }

    public function test_failure_reason_is_required(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-failed-reason'
            );

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $this->expectException(
            RuntimeException::class
        );

        $service->markFailed(
            $charge,
            '   '
        );
    }

    public function test_charge_can_be_cancelled(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-cancel'
            );

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $cancelled = $service->cancel(
            $charge
        );

        $this->assertSame(
            ChargeStatus::CANCELLED,
            $cancelled->status
        );

        $this->assertNotNull(
            $cancelled->cancelled_at
        );
    }

    public function test_paid_receivable_cannot_be_charged(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-paid-receivable'
            );

        app(
            ReceivableService::class
        )->markPaid(
            $receivable
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);
    }

    public function test_cancelled_receivable_cannot_be_charged(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-cancelled-receivable'
            );

        app(
            ReceivableService::class
        )->cancel(
            $receivable
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);
    }

    public function test_receivable_from_other_tenant_is_rejected(): void
    {
        [$tenantA] =
            $this->environment(
                'charge-service-tenant-a'
            );

        [, $receivableB] =
            $this->environment(
                'charge-service-tenant-b'
            );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableB->id,
        ]);
    }

    public function test_charge_from_other_tenant_cannot_be_changed(): void
    {
        [$tenantA] =
            $this->environment(
                'charge-service-change-a'
            );

        [, $receivableB] =
            $this->environment(
                'charge-service-change-b'
            );

        $foreign = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableB->id,
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ChargeService::class
        )->markSent(
            $foreign
        );
    }

    public function test_paid_receivable_marks_open_charges_as_paid(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-sync-paid'
            );

        $chargeService = app(
            ChargeService::class
        );

        $first = $chargeService->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $second = $chargeService->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $chargeService->markSent(
            $second
        );

        $paid = app(
            ReceivableService::class
        )->markPaid(
            $receivable,
            'PIX-CHARGE'
        );

        $chargeService->syncReceivablePaid(
            $paid
        );

        $this->assertSame(
            ChargeStatus::PAID,
            $first
                ->refresh()
                ->status
        );

        $this->assertSame(
            ChargeStatus::PAID,
            $second
                ->refresh()
                ->status
        );

        $this->assertNotNull(
            $first->refresh()->paid_at
        );

        $this->assertNotNull(
            $second->refresh()->paid_at
        );
    }

    public function test_failed_charge_can_be_cancelled(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-failed-cancel'
            );

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $failed = $service->markFailed(
            $charge,
            'Temporary error'
        );

        $cancelled = $service->cancel(
            $failed
        );

        $this->assertSame(
            ChargeStatus::CANCELLED,
            $cancelled->status
        );
    }

    public function test_paid_charge_cannot_be_cancelled(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-service-paid-cancel'
            );

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $paid = app(
            ReceivableService::class
        )->markPaid(
            $receivable
        );

        $service->syncReceivablePaid(
            $paid
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->cancel(
            $charge->refresh()
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

        $customer = Customer::query()->create([
            'tenant_id' =>
                $tenant->id,
            'type' => 'company',
            'name' =>
                'Cliente ' . $slug,
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' =>
                $customer->id,
            'title' =>
                'Titulo ' . $slug,
            'amount_minor' =>
                100000,
            'due_date' =>
                '2026-10-31',
        ]);

        return [
            $tenant,
            $receivable,
        ];
    }
}