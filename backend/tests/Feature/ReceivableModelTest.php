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
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ReceivableModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_receivable_is_created_in_current_tenant(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivable-tenant'
            );

        $receivable = $this->receivable(
            $customer
        );

        $this->assertSame(
            $tenant->id,
            $receivable->tenant_id
        );

        $this->assertSame(
            $customer->id,
            $receivable->customer_id
        );
    }

    public function test_receivable_has_expected_casts(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-casts'
            );

        $receivable = $this->receivable(
            $customer
        );

        $this->assertIsInt(
            $receivable->amount_minor
        );

        $this->assertInstanceOf(
            ReceivableStatus::class,
            $receivable->status
        );

        $this->assertNotNull(
            $receivable->due_date
        );
    }

    public function test_receivable_defaults_to_pending(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-pending'
            );

        $receivable = $this->receivable(
            $customer
        );

        $this->assertSame(
            ReceivableStatus::PENDING,
            $receivable->status
        );
    }

    public function test_receivable_can_reference_sale(): void
    {
        [
            ,
            $customer,
            $sale,
        ] = $this->environment(
            'receivable-sale',
            true
        );

        $receivable = $this->receivable(
            $customer,
            [
                'sale_id' => $sale->id,
            ]
        );

        $this->assertTrue(
            $receivable->sale->is(
                $sale
            )
        );
    }

    public function test_receivable_queries_are_isolated_by_tenant(): void
    {
        [
            $tenantA,
            $customerA,
        ] = $this->environment(
            'receivable-query-a'
        );

        $receivableA =
            $this->receivable(
                $customerA
            );

        [, $customerB] =
            $this->environment(
                'receivable-query-b'
            );

        $this->receivable(
            $customerB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            [$receivableA->id],
            Receivable::query()
                ->pluck('id')
                ->all()
        );
    }

    public function test_receivable_from_other_tenant_cannot_be_found(): void
    {
        [$tenantA] =
            $this->environment(
                'receivable-find-a'
            );

        [, $customerB] =
            $this->environment(
                'receivable-find-b'
            );

        $foreign = $this->receivable(
            $customerB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            Receivable::query()->find(
                $foreign->id
            )
        );
    }

    public function test_receivable_normalizes_title_and_currency(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-normalize'
            );

        $receivable = $this->receivable(
            $customer,
            [
                'title' =>
                    '  Parcela comercial  ',
                'currency' => 'brl',
            ]
        );

        $this->assertSame(
            'Parcela comercial',
            $receivable->title
        );

        $this->assertSame(
            'BRL',
            $receivable->currency
        );
    }

    public function test_blank_title_is_rejected(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-title'
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->receivable(
            $customer,
            [
                'title' => '   ',
            ]
        );
    }

    public function test_negative_amount_is_rejected(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-negative'
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->receivable(
            $customer,
            [
                'amount_minor' => -1,
            ]
        );
    }

    public function test_payment_fields_are_supported(): void
    {
        [, $customer] =
            $this->environment(
                'receivable-payment'
            );

        $receivable = $this->receivable(
            $customer,
            [
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' =>
                    '  PIX-123  ',
            ]
        );

        $this->assertSame(
            ReceivableStatus::PAID,
            $receivable->status
        );

        $this->assertNotNull(
            $receivable->paid_at
        );

        $this->assertSame(
            'PIX-123',
            $receivable->payment_reference
        );
    }

    public function test_parent_models_have_receivable_relations(): void
    {
        [
            $tenant,
            $customer,
            $sale,
        ] = $this->environment(
            'receivable-parents',
            true
        );

        $receivable = $this->receivable(
            $customer,
            [
                'sale_id' => $sale->id,
            ]
        );

        $this->assertTrue(
            $tenant->receivables
                ->contains(
                    $receivable
                )
        );

        $this->assertTrue(
            $customer->receivables
                ->contains(
                    $receivable
                )
        );

        $this->assertTrue(
            $sale->receivables
                ->contains(
                    $receivable
                )
        );
    }

    private function receivable(
        Customer $customer,
        array $override = []
    ): Receivable {
        return Receivable::query()->create(
            array_merge(
                [
                    'customer_id' =>
                        $customer->id,
                    'title' =>
                        'Parcela 1',
                    'currency' =>
                        'BRL',
                    'amount_minor' =>
                        100000,
                    'due_date' =>
                        now()
                            ->addDays(30)
                            ->toDateString(),
                ],
                $override
            )
        );
    }

    private function environment(
        string $slug,
        bool $withSale = false
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

        if (! $withSale) {
            return [
                $tenant,
                $customer,
                null,
            ];
        }

        $pipeline = Pipeline::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' =>
                'Pipeline ' . $slug,
            'is_default' => true,
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->create([
            'tenant_id' =>
                $tenant->id,
            'pipeline_id' =>
                $pipeline->id,
            'name' => 'Fechamento',
            'position' => 1,
            'is_active' => true,
        ]);

        $opportunity =
            Opportunity::query()->create([
                'tenant_id' =>
                    $tenant->id,
                'customer_id' =>
                    $customer->id,
                'pipeline_id' =>
                    $pipeline->id,
                'pipeline_stage_id' =>
                    $stage->id,
                'name' =>
                    'Oportunidade ' . $slug,
                'currency' => 'BRL',
                'value_minor' =>
                    100000,
                'probability' => 100,
            ]);

        $sale = Sale::query()->create([
            'tenant_id' =>
                $tenant->id,
            'customer_id' =>
                $customer->id,
            'opportunity_id' =>
                $opportunity->id,
            'number' =>
                'SALE-' . strtoupper(
                    $slug
                ),
            'currency' => 'BRL',
            'total_minor' => 100000,
            'closed_at' => now(),
            'customer_name' =>
                $customer->name,
            'opportunity_title' =>
                $opportunity->name,
        ]);

        return [
            $tenant,
            $customer,
            $sale,
        ];
    }
}