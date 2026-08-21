<?php

namespace Tests\Feature;

use App\Enums\ReceivableStatus;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\Tenant;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ReceivableServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_receivable_can_be_created(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-create'
            );

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Mensalidade',
            'amount_minor' => 250000,
            'due_date' => '2026-09-15',
        ]);

        $this->assertSame(
            'Mensalidade',
            $receivable->title
        );

        $this->assertSame(
            250000,
            $receivable->amount_minor
        );

        $this->assertSame(
            ReceivableStatus::PENDING,
            $receivable->status
        );
    }

    public function test_tenant_currency_is_default(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-currency'
            );

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Parcela',
            'amount_minor' => 10000,
            'due_date' => '2026-09-20',
        ]);

        $this->assertSame(
            'BRL',
            $receivable->currency
        );
    }

    public function test_sale_can_be_linked(): void
    {
        [
            ,
            $customer,
            $sale,
        ] = $this->environment(
            'receivable-service-sale',
            true
        );

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'title' => 'Venda',
            'amount_minor' => 100000,
            'due_date' => '2026-09-30',
        ]);

        $this->assertTrue(
            $receivable->sale->is(
                $sale
            )
        );
    }

    public function test_partial_update_preserves_existing_values(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-update'
            );

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Original',
            'amount_minor' => 100000,
            'due_date' => '2026-09-01',
        ]);

        $updated = $service->update(
            $receivable,
            [
                'title' => 'Atualizado',
            ]
        );

        $this->assertSame(
            'Atualizado',
            $updated->title
        );

        $this->assertSame(
            100000,
            $updated->amount_minor
        );

        $this->assertSame(
            '2026-09-01',
            $updated->due_date->toDateString()
        );
    }

    public function test_receivable_can_be_paid(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-paid'
            );

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Pagamento',
            'amount_minor' => 100000,
            'due_date' => '2026-09-01',
        ]);

        $paid = $service->markPaid(
            $receivable,
            'PIX-001'
        );

        $this->assertSame(
            ReceivableStatus::PAID,
            $paid->status
        );

        $this->assertNotNull(
            $paid->paid_at
        );

        $this->assertSame(
            'PIX-001',
            $paid->payment_reference
        );
    }

    public function test_receivable_can_be_cancelled(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-cancel'
            );

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Cancelar',
            'amount_minor' => 50000,
            'due_date' => '2026-09-01',
        ]);

        $cancelled = $service->cancel(
            $receivable
        );

        $this->assertSame(
            ReceivableStatus::CANCELLED,
            $cancelled->status
        );
    }

    public function test_paid_receivable_cannot_be_updated(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-paid-update'
            );

        $service = app(
            ReceivableService::class
        );

        $receivable = $service->create([
            'customer_id' => $customer->id,
            'title' => 'Pago',
            'amount_minor' => 100000,
            'due_date' => '2026-09-01',
        ]);

        $service->markPaid(
            $receivable
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->update(
            $receivable,
            [
                'title' => 'Nao permitido',
            ]
        );
    }

    public function test_customer_from_other_tenant_is_rejected(): void
    {
        [$tenantA] =
            $this->environment(
                'receivable-service-customer-a'
            );

        [, $customerB] =
            $this->environment(
                'receivable-service-customer-b'
            );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customerB->id,
            'title' => 'Foreign',
            'amount_minor' => 100000,
            'due_date' => '2026-09-01',
        ]);
    }

    public function test_sale_from_other_tenant_is_rejected(): void
    {
        [
            $tenantA,
            $customerA,
        ] = $this->environment(
            'receivable-service-sale-a'
        );

        [
            ,
            ,
            $saleB,
        ] = $this->environment(
            'receivable-service-sale-b',
            true
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customerA->id,
            'sale_id' => $saleB->id,
            'title' => 'Foreign Sale',
            'amount_minor' => 100000,
            'due_date' => '2026-09-01',
        ]);
    }

    public function test_sale_from_another_customer_is_rejected(): void
    {
        [
            ,
            $customerA,
            $saleA,
        ] = $this->environment(
            'receivable-service-sale-customer',
            true
        );

        $customerB = Customer::query()->create([
            'type' => 'company',
            'name' => 'Outro Cliente',
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customerB->id,
            'sale_id' => $saleA->id,
            'title' => 'Mismatch',
            'amount_minor' => 100000,
            'due_date' => '2026-09-01',
        ]);
    }

    public function test_negative_amount_is_rejected(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-negative'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Negativo',
            'amount_minor' => -1,
            'due_date' => '2026-09-01',
        ]);
    }

    public function test_invalid_due_date_is_rejected(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-service-date'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Data',
            'amount_minor' => 10000,
            'due_date' => '2026-02-31',
        ]);
    }

    public function test_receivable_from_other_tenant_cannot_be_updated(): void
    {
        [$tenantA] =
            $this->environment(
                'receivable-service-update-a'
            );

        [, $customerB] =
            $this->environment(
                'receivable-service-update-b'
            );

        $foreign = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customerB->id,
            'title' => 'Foreign',
            'amount_minor' => 10000,
            'due_date' => '2026-09-01',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            ReceivableService::class
        )->update(
            $foreign,
            [
                'title' => 'Blocked',
            ]
        );
    }

    private function environment(
        string $slug,
        bool $withSale = false
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

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente ' . $slug,
        ]);

        if (! $withSale) {
            return [
                $tenant,
                $customer,
                null,
            ];
        }

        $pipeline = Pipeline::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pipeline ' . $slug,
            'is_default' => true,
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->create([
            'tenant_id' => $tenant->id,
            'pipeline_id' => $pipeline->id,
            'name' => 'Fechamento',
            'position' => 1,
            'is_active' => true,
        ]);

        $opportunity =
            Opportunity::query()->create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage->id,
                'name' => 'Oportunidade ' . $slug,
                'currency' => 'BRL',
                'value_minor' => 100000,
                'probability' => 100,
            ]);

        $sale = Sale::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'number' =>
                'SALE-' . strtoupper(
                    $slug
                ),
            'currency' => 'BRL',
            'total_minor' => 100000,
            'closed_at' => now(),
            'customer_name' => $customer->name,
            'opportunity_title' => $opportunity->name,
        ]);

        return [
            $tenant,
            $customer,
            $sale,
        ];
    }
}