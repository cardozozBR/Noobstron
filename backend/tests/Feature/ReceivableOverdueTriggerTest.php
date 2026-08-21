<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\ReceivableStatus;
use App\Enums\TriggerType;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\TriggerOccurrenceRecord;
use App\Services\ReceivableOverdueTriggerService;
use App\Services\TenantContext;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReceivableOverdueTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_past_due_receivable_dispatches_and_is_recorded(): void
    {
        $tenant = $this->tenant(
            'receivable-overdue-trigger'
        );

        $customer = $this->customer(
            $tenant,
            'Overdue Customer'
        );

        $receivable = $this->receivable(
            $tenant,
            $customer,
            [
                'title' => 'Old invoice',
                'due_date' => '2026-08-10',
            ]
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->service()
            ->dispatchForDate(
                Carbon::parse(
                    '2026-08-17'
                )
            );

        $this->assertSame(
            1,
            $count
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $occurrence =
            $listener->occurrences[0];

        $this->assertSame(
            TriggerType::RECEIVABLE_OVERDUE,
            $occurrence->type
        );

        $this->assertSame(
            $tenant->id,
            $occurrence->tenantId
        );

        $this->assertSame(
            Receivable::class,
            $occurrence->subjectType
        );

        $this->assertSame(
            $receivable->id,
            $occurrence->subjectId
        );

        $this->assertSame(
            '2026-08-10',
            $occurrence->payload[
                'due_date'
            ]
        );

        $this->assertSame(
            7,
            $occurrence->payload[
                'overdue_days'
            ]
        );

        $record =
            TriggerOccurrenceRecord::query()
                ->firstOrFail();

        $this->assertSame(
            TriggerType::RECEIVABLE_OVERDUE->value,
            $record->trigger_name
        );

        $this->assertSame(
            Receivable::class,
            $record->subject_type
        );

        $this->assertSame(
            (string) $receivable->id,
            $record->subject_id
        );

        $this->assertSame(
            '2026-08-10',
            $record->boundary
        );
    }

    public function test_same_overdue_receivable_is_not_dispatched_again(): void
    {
        $tenant = $this->tenant(
            'receivable-overdue-idempotent'
        );

        $customer = $this->customer(
            $tenant,
            'Idempotent Customer'
        );

        $this->receivable(
            $tenant,
            $customer,
            [
                'due_date' =>
                    '2026-08-15',
            ]
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $service = $this->service();

        $first = $service->dispatchForDate(
            Carbon::parse(
                '2026-08-17'
            )
        );

        $second = $service->dispatchForDate(
            Carbon::parse(
                '2026-08-17'
            )
        );

        $third = $service->dispatchForDate(
            Carbon::parse(
                '2026-08-18'
            )
        );

        $this->assertSame(
            1,
            $first
        );

        $this->assertSame(
            0,
            $second
        );

        $this->assertSame(
            0,
            $third
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $this->assertSame(
            1,
            TriggerOccurrenceRecord::query()
                ->count()
        );
    }

    public function test_not_yet_overdue_receivable_is_ignored(): void
    {
        $tenant = $this->tenant(
            'receivable-not-overdue'
        );

        $customer = $this->customer(
            $tenant,
            'Current Customer'
        );

        $this->receivable(
            $tenant,
            $customer,
            [
                'due_date' =>
                    '2026-08-17',
            ]
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->service()
            ->dispatchForDate(
                Carbon::parse(
                    '2026-08-17'
                )
            );

        $this->assertSame(
            0,
            $count
        );

        $this->assertCount(
            0,
            $listener->occurrences
        );

        $this->assertSame(
            0,
            TriggerOccurrenceRecord::query()
                ->count()
        );
    }

    public function test_paid_and_cancelled_receivables_are_ignored(): void
    {
        $tenant = $this->tenant(
            'receivable-closed'
        );

        $customer = $this->customer(
            $tenant,
            'Closed Customer'
        );

        $this->receivable(
            $tenant,
            $customer,
            [
                'title' => 'Paid invoice',
                'due_date' => '2026-08-10',
                'status' =>
                    ReceivableStatus::PAID,
                'paid_at' =>
                    '2026-08-11 10:00:00',
            ]
        );

        $this->receivable(
            $tenant,
            $customer,
            [
                'title' =>
                    'Cancelled invoice',
                'due_date' =>
                    '2026-08-10',
                'status' =>
                    ReceivableStatus::CANCELLED,
            ]
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->service()
            ->dispatchForDate(
                Carbon::parse(
                    '2026-08-17'
                )
            );

        $this->assertSame(
            0,
            $count
        );

        $this->assertCount(
            0,
            $listener->occurrences
        );
    }

    public function test_query_is_isolated_to_current_tenant(): void
    {
        $tenantA = $this->tenant(
            'receivable-overdue-a'
        );

        $customerA = $this->customer(
            $tenantA,
            'Customer A'
        );

        $receivableA = $this->receivable(
            $tenantA,
            $customerA,
            [
                'title' =>
                    'Tenant A invoice',
                'due_date' =>
                    '2026-08-15',
            ]
        );

        $tenantB = $this->tenant(
            'receivable-overdue-b'
        );

        $customerB = $this->customer(
            $tenantB,
            'Customer B'
        );

        $this->receivable(
            $tenantB,
            $customerB,
            [
                'title' =>
                    'Tenant B invoice',
                'due_date' =>
                    '2026-08-15',
            ]
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->service()
            ->dispatchForDate(
                Carbon::parse(
                    '2026-08-17'
                )
            );

        $this->assertSame(
            1,
            $count
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $this->assertSame(
            $tenantA->id,
            $listener->occurrences[0]
                ->tenantId
        );

        $this->assertSame(
            $receivableA->id,
            $listener->occurrences[0]
                ->subjectId
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' => $slug,
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

        return $tenant;
    }

    private function customer(
        Tenant $tenant,
        string $name
    ): Customer {
        app(TenantContext::class)->set(
            $tenant
        );

        return Customer::query()->create([
            'tenant_id' =>
                $tenant->id,
            'type' =>
                'individual',
            'name' =>
                $name,
        ]);
    }

    private function receivable(
        Tenant $tenant,
        Customer $customer,
        array $overrides = []
    ): Receivable {
        app(TenantContext::class)->set(
            $tenant
        );

        return Receivable::query()->create(
            array_merge(
                [
                    'tenant_id' =>
                        $tenant->id,
                    'customer_id' =>
                        $customer->id,
                    'title' =>
                        'Invoice',
                    'currency' =>
                        'BRL',
                    'amount_minor' =>
                        10000,
                    'due_date' =>
                        '2026-08-15',
                    'status' =>
                        ReceivableStatus::PENDING,
                ],
                $overrides
            )
        );
    }

    private function listener(): object
    {
        return new class
            implements TriggerListener
        {
            public array $occurrences = [];

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->occurrences[] =
                    $occurrence;
            }
        };
    }

    private function bindDispatcher(
        TriggerListener $listener
    ): void {
        $dispatcher =
            new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::RECEIVABLE_OVERDUE->value,
            $listener
        );

        $this->app->instance(
            TriggerDispatcher::class,
            $dispatcher
        );
    }

    private function service():
        ReceivableOverdueTriggerService
    {
        return $this->app->make(
            ReceivableOverdueTriggerService::class
        );
    }
}