<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Services\FinancialIndicatorService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinancialIndicatorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_received_total_uses_paid_receivables(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'financial-received'
            );

        $service = app(
            ReceivableService::class
        );

        $paid = $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Paid',
            'amount_minor' =>
                150000,
            'due_date' =>
                '2026-08-01',
        ]);

        $service->markPaid(
            $paid,
            'PAY-001'
        );

        $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Pending',
            'amount_minor' =>
                300000,
            'due_date' =>
                '2026-09-01',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            150000,
            app(
                FinancialIndicatorService::class
            )->receivedMinor()
        );
    }

    public function test_outstanding_total_uses_pending_receivables(): void
    {
        [, $customer] =
            $this->environment(
                'financial-outstanding'
            );

        $service = app(
            ReceivableService::class
        );

        $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'A',
            'amount_minor' =>
                100000,
            'due_date' =>
                '2026-09-01',
        ]);

        $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'B',
            'amount_minor' =>
                250000,
            'due_date' =>
                '2026-09-15',
        ]);

        $this->assertSame(
            350000,
            app(
                FinancialIndicatorService::class
            )->outstandingMinor()
        );
    }

    public function test_overdue_total_uses_pending_past_due_receivables(): void
    {
        [, $customer] =
            $this->environment(
                'financial-overdue'
            );

        $service = app(
            ReceivableService::class
        );

        $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Overdue',
            'amount_minor' =>
                80000,
            'due_date' =>
                '2026-08-10',
        ]);

        $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Future',
            'amount_minor' =>
                90000,
            'due_date' =>
                '2026-08-20',
        ]);

        $this->assertSame(
            80000,
            app(
                FinancialIndicatorService::class
            )->overdueMinor(
                Carbon::parse(
                    '2026-08-16'
                )
            )
        );
    }

    public function test_paid_receivable_is_not_overdue(): void
    {
        [, $customer] =
            $this->environment(
                'financial-paid-overdue'
            );

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Old paid',
            'amount_minor' =>
                120000,
            'due_date' =>
                '2026-08-01',
        ]);

        app(
            ReceivableService::class
        )->markPaid(
            $receivable
        );

        $this->assertSame(
            0,
            app(
                FinancialIndicatorService::class
            )->overdueMinor(
                Carbon::parse(
                    '2026-08-16'
                )
            )
        );
    }

    public function test_received_total_can_be_filtered_by_period(): void
    {
        [, $customer] =
            $this->environment(
                'financial-period-filter'
            );

        $service = app(
            ReceivableService::class
        );

        Carbon::setTestNow(
            '2026-08-10 10:00:00'
        );

        $first = $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'August',
            'amount_minor' =>
                100000,
            'due_date' =>
                '2026-08-10',
        ]);

        $service->markPaid(
            $first
        );

        Carbon::setTestNow(
            '2026-09-10 10:00:00'
        );

        $second = $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'September',
            'amount_minor' =>
                200000,
            'due_date' =>
                '2026-09-10',
        ]);

        $service->markPaid(
            $second
        );

        Carbon::setTestNow();

        $this->assertSame(
            100000,
            app(
                FinancialIndicatorService::class
            )->receivedMinor(
                Carbon::parse(
                    '2026-08-01 00:00:00'
                ),
                Carbon::parse(
                    '2026-08-31 23:59:59'
                )
            )
        );
    }

    public function test_revenue_by_period_groups_paid_receivables_by_day(): void
    {
        [, $customer] =
            $this->environment(
                'financial-period'
            );

        $service = app(
            ReceivableService::class
        );

        Carbon::setTestNow(
            '2026-08-10 10:00:00'
        );

        foreach ([
            100000,
            50000,
        ] as $amount) {
            $receivable =
                $service->create([
                    'customer_id' =>
                        $customer->id,
                    'title' =>
                        'Revenue',
                    'amount_minor' =>
                        $amount,
                    'due_date' =>
                        '2026-08-10',
                ]);

            $service->markPaid(
                $receivable
            );
        }

        Carbon::setTestNow(
            '2026-08-11 10:00:00'
        );

        $receivable =
            $service->create([
                'customer_id' =>
                    $customer->id,
                'title' =>
                    'Revenue second day',
                'amount_minor' =>
                    25000,
                'due_date' =>
                    '2026-08-11',
            ]);

        $service->markPaid(
            $receivable
        );

        Carbon::setTestNow();

        $result = app(
            FinancialIndicatorService::class
        )->revenueByPeriod(
            Carbon::parse(
                '2026-08-01 00:00:00'
            ),
            Carbon::parse(
                '2026-08-31 23:59:59'
            )
        );

        $this->assertSame(
            [
                [
                    'date' =>
                        '2026-08-10',
                    'amount_minor' =>
                        150000,
                ],
                [
                    'date' =>
                        '2026-08-11',
                    'amount_minor' =>
                        25000,
                ],
            ],
            $result->all()
        );
    }

    public function test_revenue_by_customer_aggregates_paid_receivables(): void
    {
        [$tenant, $customerA] =
            $this->environment(
                'financial-customer-a'
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $customerB =
            Customer::query()->create([
                'tenant_id' =>
                    $tenant->id,
                'type' => 'company',
                'name' =>
                    'Customer B',
            ]);

        $service = app(
            ReceivableService::class
        );

        foreach ([
            100000,
            50000,
        ] as $amount) {
            $receivable =
                $service->create([
                    'customer_id' =>
                        $customerA->id,
                    'title' =>
                        'Customer A',
                    'amount_minor' =>
                        $amount,
                    'due_date' =>
                        '2026-08-10',
                ]);

            $service->markPaid(
                $receivable
            );
        }

        $receivable =
            $service->create([
                'customer_id' =>
                    $customerB->id,
                'title' =>
                    'Customer B',
                'amount_minor' =>
                    80000,
                'due_date' =>
                    '2026-08-10',
            ]);

        $service->markPaid(
            $receivable
        );

        $result = app(
            FinancialIndicatorService::class
        )->revenueByCustomer();

        $this->assertSame(
            $customerA->id,
            $result[0]['customer_id']
        );

        $this->assertSame(
            150000,
            $result[0]['amount_minor']
        );

        $this->assertSame(
            $customerB->id,
            $result[1]['customer_id']
        );

        $this->assertSame(
            80000,
            $result[1]['amount_minor']
        );
    }

    public function test_summary_returns_core_financial_metrics(): void
    {
        [, $customer] =
            $this->environment(
                'financial-summary'
            );

        $service = app(
            ReceivableService::class
        );

        $paid = $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Paid',
            'amount_minor' =>
                100000,
            'due_date' =>
                '2026-08-01',
        ]);

        $service->markPaid(
            $paid
        );

        $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Overdue',
            'amount_minor' =>
                200000,
            'due_date' =>
                '2026-08-10',
        ]);

        $service->create([
            'customer_id' =>
                $customer->id,
            'title' => 'Future',
            'amount_minor' =>
                300000,
            'due_date' =>
                '2026-09-01',
        ]);

        $summary = app(
            FinancialIndicatorService::class
        )->summary(
            Carbon::parse(
                '2026-08-16'
            )
        );

        $this->assertSame(
            100000,
            $summary['received_minor']
        );

        $this->assertSame(
            500000,
            $summary['outstanding_minor']
        );

        $this->assertSame(
            200000,
            $summary['overdue_minor']
        );
    }

    public function test_financial_indicators_are_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $customerA,
        ] = $this->environment(
            'financial-tenant-a'
        );

        $service = app(
            ReceivableService::class
        );

        $receivableA =
            $service->create([
                'customer_id' =>
                    $customerA->id,
                'title' =>
                    'Tenant A',
                'amount_minor' =>
                    100000,
                'due_date' =>
                    '2026-08-01',
            ]);

        $service->markPaid(
            $receivableA
        );

        [
            ,
            $customerB,
        ] = $this->environment(
            'financial-tenant-b'
        );

        $receivableB =
            $service->create([
                'customer_id' =>
                    $customerB->id,
                'title' =>
                    'Tenant B',
                'amount_minor' =>
                    900000,
                'due_date' =>
                    '2026-08-01',
            ]);

        $service->markPaid(
            $receivableB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            100000,
            app(
                FinancialIndicatorService::class
            )->receivedMinor()
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

        return [
            $tenant,
            $customer,
        ];
    }
}