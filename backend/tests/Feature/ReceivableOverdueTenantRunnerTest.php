<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\ReceivableStatus;
use App\Enums\TriggerType;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\TriggerOccurrenceRecord;
use App\Services\ReceivableOverdueTenantRunner;
use App\Services\TenantContext;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class ReceivableOverdueTenantRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runner_processes_all_active_tenants(): void
    {
        $tenantA = $this->tenant(
            'runner-active-a',
            'America/Fortaleza'
        );

        $customerA = $this->customer(
            $tenantA,
            'Customer A'
        );

        $receivableA = $this->receivable(
            $tenantA,
            $customerA,
            'Tenant A invoice',
            '2026-08-15'
        );

        $tenantB = $this->tenant(
            'runner-active-b',
            'America/Sao_Paulo'
        );

        $customerB = $this->customer(
            $tenantB,
            'Customer B'
        );

        $receivableB = $this->receivable(
            $tenantB,
            $customerB,
            'Tenant B invoice',
            '2026-08-14'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->runner()->dispatch(
            Carbon::parse(
                '2026-08-17 15:00:00',
                'UTC'
            )
        );

        $this->assertSame(
            2,
            $count
        );

        $this->assertCount(
            2,
            $listener->occurrences
        );

        $this->assertSame(
            [
                $tenantA->id,
                $tenantB->id,
            ],
            array_map(
                fn (
                    TriggerOccurrence $occurrence
                ): int =>
                    $occurrence->tenantId,
                $listener->occurrences
            )
        );

        $this->assertSame(
            [
                $receivableA->id,
                $receivableB->id,
            ],
            array_map(
                fn (
                    TriggerOccurrence $occurrence
                ): int =>
                    (int) $occurrence->subjectId,
                $listener->occurrences
            )
        );

        $this->assertSame(
            2,
            TriggerOccurrenceRecord::withoutGlobalScope(
                'tenant'
            )->count()
        );
    }

    public function test_inactive_tenant_is_not_processed(): void
    {
        $active = $this->tenant(
            'runner-active',
            'America/Fortaleza'
        );

        $activeCustomer = $this->customer(
            $active,
            'Active Customer'
        );

        $this->receivable(
            $active,
            $activeCustomer,
            'Active invoice',
            '2026-08-15'
        );

        $inactive = $this->tenant(
            'runner-inactive',
            'America/Fortaleza',
            'inactive'
        );

        $inactiveCustomer = $this->customer(
            $inactive,
            'Inactive Customer'
        );

        $this->receivable(
            $inactive,
            $inactiveCustomer,
            'Inactive invoice',
            '2026-08-15'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->runner()->dispatch(
            Carbon::parse(
                '2026-08-17 12:00:00',
                'UTC'
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
            $active->id,
            $listener->occurrences[0]
                ->tenantId
        );
    }

    public function test_runner_uses_each_tenant_local_date(): void
    {
        $fortaleza = $this->tenant(
            'runner-fortaleza',
            'America/Fortaleza'
        );

        $customerFortaleza = $this->customer(
            $fortaleza,
            'Fortaleza Customer'
        );

        $this->receivable(
            $fortaleza,
            $customerFortaleza,
            'Fortaleza invoice',
            '2026-08-16'
        );

        $tokyo = $this->tenant(
            'runner-tokyo',
            'Asia/Tokyo'
        );

        $customerTokyo = $this->customer(
            $tokyo,
            'Tokyo Customer'
        );

        $this->receivable(
            $tokyo,
            $customerTokyo,
            'Tokyo invoice',
            '2026-08-16'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        /*
         * UTC 2026-08-16 18:00:
         *
         * Fortaleza = 2026-08-16 15:00
         * Tokyo     = 2026-08-17 03:00
         *
         * due_date 2026-08-16 is not overdue in Fortaleza,
         * but is overdue in Tokyo.
         */
        $count = $this->runner()->dispatch(
            Carbon::parse(
                '2026-08-16 18:00:00',
                'UTC'
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
            $tokyo->id,
            $listener->occurrences[0]
                ->tenantId
        );
    }

    public function test_runner_is_idempotent_between_runs(): void
    {
        $tenant = $this->tenant(
            'runner-idempotent',
            'America/Fortaleza'
        );

        $customer = $this->customer(
            $tenant,
            'Idempotent Customer'
        );

        $this->receivable(
            $tenant,
            $customer,
            'Idempotent invoice',
            '2026-08-15'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $runner = $this->runner();

        $now = Carbon::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $this->assertSame(
            1,
            $runner->dispatch(
                $now
            )
        );

        $this->assertSame(
            0,
            $runner->dispatch(
                $now
            )
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );
    }

    public function test_runner_clears_tenant_context_after_execution(): void
    {
        $tenant = $this->tenant(
            'runner-context-clear',
            'America/Fortaleza'
        );

        $customer = $this->customer(
            $tenant,
            'Context Customer'
        );

        $this->receivable(
            $tenant,
            $customer,
            'Context invoice',
            '2026-08-15'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $this->runner()->dispatch(
            Carbon::parse(
                '2026-08-17 12:00:00',
                'UTC'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            TenantContext::class
        )->get();
    }

    private function tenant(
        string $slug,
        string $timezone,
        string $status = 'active'
    ): Tenant {
        return Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'status' => $status,
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => $timezone,
            'currency' => 'BRL',
        ]);
    }

    private function customer(
        Tenant $tenant,
        string $name
    ): Customer {
        app(TenantContext::class)->set(
            $tenant
        );

        return Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => $name,
        ]);
    }

    private function receivable(
        Tenant $tenant,
        Customer $customer,
        string $title,
        string $dueDate
    ): Receivable {
        app(TenantContext::class)->set(
            $tenant
        );

        return Receivable::query()->create([
            'tenant_id' =>
                $tenant->id,
            'customer_id' =>
                $customer->id,
            'title' =>
                $title,
            'currency' =>
                'BRL',
            'amount_minor' =>
                10000,
            'due_date' =>
                $dueDate,
            'status' =>
                ReceivableStatus::PENDING,
        ]);
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

    private function runner():
        ReceivableOverdueTenantRunner
    {
        return $this->app->make(
            ReceivableOverdueTenantRunner::class
        );
    }
}