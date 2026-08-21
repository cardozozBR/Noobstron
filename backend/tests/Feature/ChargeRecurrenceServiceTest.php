<?php

namespace Tests\Feature;

use App\Enums\ChargeRecurrenceFrequency;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ChargeRecurrenceService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class ChargeRecurrenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_recurrence_can_be_created(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-create'
            );

        $recurrence = app(
            ChargeRecurrenceService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'next_run_at' =>
                '2026-09-01 09:00:00',
            'channel' => 'email',
            'recipient' =>
                'billing@example.com',
        ]);

        $this->assertSame(
            ChargeRecurrenceFrequency::MONTHLY,
            $recurrence->frequency
        );

        $this->assertTrue(
            $recurrence->is_active
        );

        $this->assertSame(
            1,
            $recurrence->interval_count
        );
    }

    public function test_due_returns_only_due_active_recurrences(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-due'
            );

        $service = app(
            ChargeRecurrenceService::class
        );

        $due = $service->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'daily',
            'next_run_at' =>
                '2026-08-16 08:00:00',
        ]);

        $service->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'daily',
            'next_run_at' =>
                '2026-08-18 08:00:00',
        ]);

        $result = $service->due(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertSame(
            [$due->id],
            $result
                ->pluck('id')
                ->all()
        );
    }

    public function test_processing_due_recurrence_creates_charge(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-process'
            );

        $service = app(
            ChargeRecurrenceService::class
        );

        $recurrence = $service->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'monthly',
            'next_run_at' =>
                '2026-08-16 08:00:00',
            'channel' => 'email',
            'recipient' =>
                'finance@example.com',
        ]);

        $charge = $service->process(
            $recurrence,
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertNotNull(
            $charge
        );

        $this->assertSame(
            $receivable->id,
            $charge->receivable_id
        );

        $this->assertSame(
            'finance@example.com',
            $charge->recipient
        );

        $this->assertSame(
            1,
            Charge::query()->count()
        );
    }

    public function test_monthly_processing_advances_next_run(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-monthly'
            );

        $service = app(
            ChargeRecurrenceService::class
        );

        $recurrence = $service->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'next_run_at' =>
                '2026-08-31 08:00:00',
        ]);

        $service->process(
            $recurrence,
            Carbon::parse(
                '2026-08-31 12:00:00'
            )
        );

        $this->assertSame(
            '2026-09-30 08:00:00',
            $recurrence
                ->refresh()
                ->next_run_at
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_weekly_interval_is_respected(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-weekly'
            );

        $service = app(
            ChargeRecurrenceService::class
        );

        $recurrence = $service->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'weekly',
            'interval_count' => 2,
            'next_run_at' =>
                '2026-08-16 08:00:00',
        ]);

        $service->process(
            $recurrence,
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertSame(
            '2026-08-30 08:00:00',
            $recurrence
                ->refresh()
                ->next_run_at
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_recurrence_is_deactivated_after_end_date(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-end'
            );

        $service = app(
            ChargeRecurrenceService::class
        );

        $recurrence = $service->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'monthly',
            'next_run_at' =>
                '2026-08-16 08:00:00',
            'ends_at' =>
                '2026-08-20 23:59:59',
        ]);

        $service->process(
            $recurrence,
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertFalse(
            $recurrence
                ->refresh()
                ->is_active
        );

        $this->assertSame(
            1,
            Charge::query()->count()
        );
    }

    public function test_paid_receivable_deactivates_recurrence(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-paid'
            );

        $service = app(
            ChargeRecurrenceService::class
        );

        $recurrence = $service->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' => 'monthly',
            'next_run_at' =>
                '2026-08-16 08:00:00',
        ]);

        app(
            ReceivableService::class
        )->markPaid(
            $receivable
        );

        $result = $service->process(
            $recurrence,
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertNull(
            $result
        );

        $this->assertFalse(
            $recurrence
                ->refresh()
                ->is_active
        );

        $this->assertSame(
            0,
            Charge::query()->count()
        );
    }

    public function test_recurrence_can_be_cancelled(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-cancel'
            );

        $service = app(
            ChargeRecurrenceService::class
        );

        $recurrence = $service->create([
            'receivable_id' =>
                $receivable->id,
            'next_run_at' =>
                '2026-09-01 08:00:00',
        ]);

        $cancelled = $service->cancel(
            $recurrence
        );

        $this->assertFalse(
            $cancelled->is_active
        );
    }

    public function test_invalid_interval_is_rejected(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-interval'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ChargeRecurrenceService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
            'interval_count' => 0,
            'next_run_at' =>
                '2026-09-01 08:00:00',
        ]);
    }

    public function test_end_before_next_run_is_rejected(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-recurrence-invalid-end'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ChargeRecurrenceService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
            'next_run_at' =>
                '2026-09-10 08:00:00',
            'ends_at' =>
                '2026-09-01 08:00:00',
        ]);
    }

    public function test_other_tenant_receivable_is_rejected(): void
    {
        [$tenantA] =
            $this->environment(
                'charge-recurrence-a'
            );

        [, $receivableB] =
            $this->environment(
                'charge-recurrence-b'
            );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ChargeRecurrenceService::class
        )->create([
            'receivable_id' =>
                $receivableB->id,
            'next_run_at' =>
                '2026-09-01 08:00:00',
        ]);
    }

    public function test_due_recurrences_are_isolated_by_tenant(): void
    {
        [
            $tenantA,
            $receivableA,
        ] = $this->environment(
            'charge-recurrence-query-a'
        );

        $service = app(
            ChargeRecurrenceService::class
        );

        $recurrenceA = $service->create([
            'receivable_id' =>
                $receivableA->id,
            'next_run_at' =>
                '2026-08-16 08:00:00',
        ]);

        [
            ,
            $receivableB,
        ] = $this->environment(
            'charge-recurrence-query-b'
        );

        $service->create([
            'receivable_id' =>
                $receivableB->id,
            'next_run_at' =>
                '2026-08-16 08:00:00',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $result = $service->due(
            Carbon::parse(
                '2026-08-16 12:00:00'
            )
        );

        $this->assertSame(
            [$recurrenceA->id],
            $result
                ->pluck('id')
                ->all()
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
                '2026-12-31',
        ]);

        return [
            $tenant,
            $receivable,
        ];
    }
}