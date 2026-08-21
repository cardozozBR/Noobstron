<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ChargeQueryService;
use App\Services\ChargeService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChargeQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_charges_can_be_listed_until_date(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-query-scheduled'
            );

        $service = app(
            ChargeService::class
        );

        $first = $service->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-17 10:00:00',
        ]);

        $service->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-20 10:00:00',
        ]);

        $result = app(
            ChargeQueryService::class
        )->scheduledUntil(
            Carbon::parse(
                '2026-08-18 23:59:59'
            )
        );

        $this->assertSame(
            [$first->id],
            $result
                ->pluck('id')
                ->all()
        );
    }

    public function test_due_for_reminder_returns_pending_scheduled_charges(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-query-reminder'
            );

        $service = app(
            ChargeService::class
        );

        $due = $service->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-16 08:00:00',
        ]);

        $future = $service->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-18 08:00:00',
        ]);

        $result = app(
            ChargeQueryService::class
        )->dueForReminder(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $ids = $result
            ->pluck('id')
            ->all();

        $this->assertContains(
            $due->id,
            $ids
        );

        $this->assertNotContains(
            $future->id,
            $ids
        );
    }

    public function test_sent_charge_is_not_due_for_reminder(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-query-sent'
            );

        $service = app(
            ChargeService::class
        );

        $charge = $service->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-16 08:00:00',
        ]);

        $service->markSent(
            $charge
        );

        $result = app(
            ChargeQueryService::class
        )->dueForReminder(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertFalse(
            $result->contains(
                'id',
                $charge->id
            )
        );
    }

    public function test_overdue_returns_charge_for_overdue_receivable(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-query-overdue',
                '2026-08-10'
            );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-10 08:00:00',
        ]);

        $result = app(
            ChargeQueryService::class
        )->overdue(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertTrue(
            $result->contains(
                'id',
                $charge->id
            )
        );
    }

    public function test_future_receivable_is_not_overdue(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-query-not-overdue',
                '2026-08-20'
            );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-15 08:00:00',
        ]);

        $result = app(
            ChargeQueryService::class
        )->overdue(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertFalse(
            $result->contains(
                'id',
                $charge->id
            )
        );
    }

    public function test_paid_charge_is_not_overdue(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-query-paid-overdue',
                '2026-08-10'
            );

        $chargeService = app(
            ChargeService::class
        );

        $charge = $chargeService->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-10 08:00:00',
        ]);

        $paid = app(
            ReceivableService::class
        )->markPaid(
            $receivable
        );

        $chargeService->syncReceivablePaid(
            $paid
        );

        $this->assertSame(
            ChargeStatus::PAID,
            $charge->refresh()->status
        );

        $result = app(
            ChargeQueryService::class
        )->overdue(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertFalse(
            $result->contains(
                'id',
                $charge->id
            )
        );
    }

    public function test_upcoming_returns_only_requested_window(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-query-upcoming'
            );

        $service = app(
            ChargeService::class
        );

        $inside = $service->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-18 10:00:00',
        ]);

        $service->create([
            'receivable_id' =>
                $receivable->id,
            'scheduled_at' =>
                '2026-08-25 10:00:00',
        ]);

        $result = app(
            ChargeQueryService::class
        )->upcoming(
            Carbon::parse(
                '2026-08-17 00:00:00'
            ),
            Carbon::parse(
                '2026-08-20 23:59:59'
            )
        );

        $this->assertSame(
            [$inside->id],
            $result
                ->pluck('id')
                ->all()
        );
    }

    public function test_charge_queries_are_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $receivableA,
        ] = $this->environment(
            'charge-query-tenant-a'
        );

        $chargeA = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableA->id,
            'scheduled_at' =>
                '2026-08-16 08:00:00',
        ]);

        [
            ,
            $receivableB,
        ] = $this->environment(
            'charge-query-tenant-b'
        );

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableB->id,
            'scheduled_at' =>
                '2026-08-16 08:00:00',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $result = app(
            ChargeQueryService::class
        )->dueForReminder(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertSame(
            [$chargeA->id],
            $result
                ->pluck('id')
                ->all()
        );
    }

    private function environment(
        string $slug,
        string $dueDate = '2026-10-31'
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
                $dueDate,
        ]);

        return [
            $tenant,
            $receivable,
        ];
    }
}