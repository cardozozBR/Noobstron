<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\TriggerType;
use App\Models\Customer;
use App\Models\CustomerHistory;
use App\Models\Tenant;
use App\Models\TriggerOccurrenceRecord;
use App\Services\CustomerInactiveTriggerService;
use App\Services\TenantContext;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class CustomerInactiveTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_without_recent_activity_dispatches_trigger(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-05-01 10:00:00',
                'UTC'
            )
        );

        $tenant = $this->tenant(
            'inactive-customer'
        );

        $customer = $this->customer(
            $tenant,
            'Inactive Customer',
            '2026-01-01 10:00:00'
        );

        $this->history(
            $customer,
            'customer.updated',
            '2026-02-01 10:00:00'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->service()
            ->dispatchForDate(
                60,
                Carbon::parse(
                    '2026-05-01'
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
            TriggerType::CUSTOMER_INACTIVE,
            $occurrence->type
        );

        $this->assertSame(
            $tenant->id,
            $occurrence->tenantId
        );

        $this->assertSame(
            Customer::class,
            $occurrence->subjectType
        );

        $this->assertSame(
            $customer->id,
            $occurrence->subjectId
        );

        $this->assertSame(
            60,
            $occurrence->payload[
                'inactive_days'
            ]
        );

        $this->assertSame(
            '2026-04-02',
            $occurrence->payload[
                'inactive_since'
            ]
        );

        $this->assertSame(
            1,
            TriggerOccurrenceRecord::query()
                ->count()
        );
    }

    public function test_recent_history_keeps_customer_active(): void
    {
        $tenant = $this->tenant(
            'active-customer'
        );

        $customer = $this->customer(
            $tenant,
            'Active Customer',
            '2026-01-01 10:00:00'
        );

        $this->history(
            $customer,
            'customer.email.updated',
            '2026-04-20 10:00:00'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->service()
            ->dispatchForDate(
                60,
                Carbon::parse(
                    '2026-05-01'
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

    public function test_customer_without_history_uses_created_at(): void
    {
        $tenant = $this->tenant(
            'legacy-inactive-customer'
        );

        $customer = $this->customer(
            $tenant,
            'Legacy Customer',
            '2026-01-01 10:00:00'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $count = $this->service()
            ->dispatchForDate(
                30,
                Carbon::parse(
                    '2026-03-01'
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
            $customer->id,
            $listener->occurrences[0]
                ->subjectId
        );
    }

    public function test_same_inactive_period_is_idempotent(): void
    {
        $tenant = $this->tenant(
            'inactive-idempotent'
        );

        $customer = $this->customer(
            $tenant,
            'Idempotent Customer',
            '2026-01-01 10:00:00'
        );

        $this->history(
            $customer,
            'customer.created',
            '2026-01-01 10:00:00'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $service = $this->service();

        $this->assertSame(
            1,
            $service->dispatchForDate(
                30,
                Carbon::parse(
                    '2026-03-01'
                )
            )
        );

        $this->assertSame(
            0,
            $service->dispatchForDate(
                30,
                Carbon::parse(
                    '2026-03-02'
                )
            )
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

    public function test_new_activity_allows_future_inactive_occurrence(): void
    {
        $tenant = $this->tenant(
            'inactive-reactivated'
        );

        $customer = $this->customer(
            $tenant,
            'Reactivated Customer',
            '2026-01-01 10:00:00'
        );

        $this->history(
            $customer,
            'customer.created',
            '2026-01-01 10:00:00'
        );

        $listener = $this->listener();

        $this->bindDispatcher(
            $listener
        );

        $service = $this->service();

        $this->assertSame(
            1,
            $service->dispatchForDate(
                30,
                Carbon::parse(
                    '2026-02-15'
                )
            )
        );

        $this->history(
            $customer,
            'customer.updated',
            '2026-03-01 10:00:00'
        );

        $this->assertSame(
            1,
            $service->dispatchForDate(
                30,
                Carbon::parse(
                    '2026-04-15'
                )
            )
        );

        $this->assertCount(
            2,
            $listener->occurrences
        );

        $this->assertSame(
            2,
            TriggerOccurrenceRecord::query()
                ->count()
        );
    }

    public function test_customer_query_is_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'inactive-tenant-a'
        );

        $customerA = $this->customer(
            $tenantA,
            'Customer A',
            '2026-01-01 10:00:00'
        );

        $tenantB = $this->tenant(
            'inactive-tenant-b'
        );

        $this->customer(
            $tenantB,
            'Customer B',
            '2026-01-01 10:00:00'
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
                30,
                Carbon::parse(
                    '2026-03-01'
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
            $customerA->id,
            $listener->occurrences[0]
                ->subjectId
        );
    }

    public function test_inactive_days_must_be_positive(): void
    {
        $tenant = $this->tenant(
            'inactive-invalid-days'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service()
            ->dispatchForDate(
                0,
                Carbon::parse(
                    '2026-03-01'
                )
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
        string $name,
        string $createdAt
    ): Customer {
        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => $name,
        ]);

        Customer::query()
            ->whereKey(
                $customer->id
            )
            ->update([
                'created_at' =>
                    $createdAt,
                'updated_at' =>
                    $createdAt,
            ]);

        return Customer::query()
            ->findOrFail(
                $customer->id
            );
    }

    private function history(
        Customer $customer,
        string $event,
        string $createdAt
    ): CustomerHistory {
        app(TenantContext::class)->set(
            $customer->tenant
        );

        $history = CustomerHistory::query()
            ->create([
                'tenant_id' =>
                    $customer->tenant_id,
                'customer_id' =>
                    $customer->id,
                'event' =>
                    $event,
            ]);

        CustomerHistory::query()
            ->whereKey(
                $history->id
            )
            ->update([
                'created_at' =>
                    $createdAt,
                'updated_at' =>
                    $createdAt,
            ]);

        return CustomerHistory::query()
            ->findOrFail(
                $history->id
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
            TriggerType::CUSTOMER_INACTIVE->value,
            $listener
        );

        $this->app->instance(
            TriggerDispatcher::class,
            $dispatcher
        );
    }

    private function service():
        CustomerInactiveTriggerService
    {
        return $this->app->make(
            CustomerInactiveTriggerService::class
        );
    }
}