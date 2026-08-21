<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\Sale;
use App\Models\Tenant;
use App\Services\SaleService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_can_be_closed_directly(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-service-direct');

        $sale = app(SaleService::class)
            ->close($opportunity);

        $this->assertSame(
            $tenant->id,
            $sale->tenant_id
        );

        $this->assertSame(
            $customer->id,
            $sale->customer_id
        );

        $this->assertSame(
            $opportunity->id,
            $sale->opportunity_id
        );

        $this->assertSame(
            100000,
            $sale->total_minor
        );

        $this->assertSame(
            'BRL',
            $sale->currency
        );
    }

    public function test_direct_close_can_override_final_value(): void
    {
        [, , $opportunity] =
            $this->environment('sale-service-value');

        $sale = app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'total_minor' => 125000,
                    'currency' => 'usd',
                ]
            );

        $this->assertSame(
            125000,
            $sale->total_minor
        );

        $this->assertSame(
            'USD',
            $sale->currency
        );
    }

    public function test_accepted_proposal_is_source_of_final_value(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-service-proposal');

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity,
            'accepted',
            175000,
            'USD'
        );

        $sale = app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'proposal_id' =>
                        $proposal->id,

                    /*
                     * Estes valores devem ser ignorados quando
                     * existe proposta aceita.
                     */
                    'total_minor' => 1,
                    'currency' => 'BRL',
                ]
            );

        $this->assertSame(
            $proposal->id,
            $sale->proposal_id
        );

        $this->assertSame(
            175000,
            $sale->total_minor
        );

        $this->assertSame(
            'USD',
            $sale->currency
        );

        $this->assertSame(
            $proposal->number,
            $sale->proposal_number
        );
    }

    public function test_non_accepted_proposal_cannot_close_sale(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-service-draft');

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity,
            'draft'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'proposal_id' =>
                        $proposal->id,
                ]
            );
    }

    public function test_proposal_from_another_opportunity_is_rejected(): void
    {
        [$tenant, $customer, $opportunityA] =
            $this->environment('sale-service-opportunity-a');

        [, , $opportunityB] =
            $this->secondOpportunity(
                $tenant,
                $customer,
                'sale-service-opportunity-b'
            );

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunityB,
            'accepted'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(SaleService::class)
            ->close(
                $opportunityA,
                [
                    'proposal_id' =>
                        $proposal->id,
                ]
            );
    }

    public function test_proposal_from_another_customer_is_rejected(): void
    {
        [$tenant, $customerA, $opportunity] =
            $this->environment('sale-service-customer-a');

        app(TenantContext::class)->set(
            $tenant
        );

        $customerB = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente B',
        ]);

        $proposal = Proposal::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customerB->id,
            'opportunity_id' => $opportunity->id,
            'number' => 'PROP-CUSTOMER-B',
            'status' => 'accepted',
            'currency' => 'BRL',
            'subtotal_minor' => 100000,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 100000,
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'proposal_id' =>
                        $proposal->id,
                ]
            );
    }

    public function test_opportunity_cannot_be_closed_twice(): void
    {
        [, , $opportunity] =
            $this->environment('sale-service-duplicate');

        $service = app(
            SaleService::class
        );

        $service->close(
            $opportunity
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->close(
            $opportunity
        );
    }

    public function test_negative_final_value_is_rejected(): void
    {
        [, , $opportunity] =
            $this->environment('sale-service-negative');

        $this->expectException(
            RuntimeException::class
        );

        app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'total_minor' => -1,
                ]
            );
    }

    public function test_invalid_currency_is_rejected(): void
    {
        [, , $opportunity] =
            $this->environment('sale-service-currency');

        $this->expectException(
            RuntimeException::class
        );

        app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'currency' => 'REAL',
                ]
            );
    }

    public function test_sale_snapshots_are_preserved(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-service-snapshot');

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity,
            'accepted'
        );

        $sale = app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'proposal_id' =>
                        $proposal->id,
                ]
            );

        $customerName = $customer->name;
        $opportunityName = $opportunity->name;
        $proposalNumber = $proposal->number;

        $customer->update([
            'name' => 'Cliente alterado',
        ]);

        $opportunity->update([
            'name' => 'Oportunidade alterada',
        ]);

        $proposal->update([
            'number' => 'PROP-ALTERADA',
        ]);

        $sale->refresh();

        $this->assertSame(
            $customerName,
            $sale->customer_name
        );

        $this->assertSame(
            $opportunityName,
            $sale->opportunity_title
        );

        $this->assertSame(
            $proposalNumber,
            $sale->proposal_number
        );
    }

    public function test_custom_sale_number_is_normalized(): void
    {
        [, , $opportunity] =
            $this->environment('sale-service-number');

        $sale = app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'number' => ' sale-custom-01 ',
                ]
            );

        $this->assertSame(
            'SALE-CUSTOM-01',
            $sale->number
        );
    }

    public function test_generated_sale_number_is_created(): void
    {
        [, , $opportunity] =
            $this->environment('sale-service-auto-number');

        $sale = app(SaleService::class)
            ->close(
                $opportunity
            );

        $this->assertStringStartsWith(
            'SALE-',
            $sale->number
        );
    }

    public function test_opportunity_from_other_tenant_cannot_be_closed(): void
    {
        [$tenantA] =
            $this->environment('sale-service-tenant-a');

        [$tenantB, , $opportunityB] =
            $this->environment('sale-service-tenant-b');

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(SaleService::class)
            ->close(
                $opportunityB
            );
    }

    public function test_proposal_from_other_tenant_cannot_be_used(): void
    {
        [$tenantA, , $opportunityA] =
            $this->environment('sale-service-proposal-tenant-a');

        [$tenantB, $customerB, $opportunityB] =
            $this->environment('sale-service-proposal-tenant-b');

        $proposalB = $this->proposal(
            $tenantB,
            $customerB,
            $opportunityB,
            'accepted'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(SaleService::class)
            ->close(
                $opportunityA,
                [
                    'proposal_id' =>
                        $proposalB->id,
                ]
            );
    }

    public function test_failed_close_does_not_create_partial_sale(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-service-transaction');

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity,
            'draft'
        );

        try {
            app(SaleService::class)
                ->close(
                    $opportunity,
                    [
                        'proposal_id' =>
                            $proposal->id,
                    ]
                );

            $this->fail(
                'Expected close failure.'
            );
        } catch (RuntimeException) {
            //
        }

        $this->assertSame(
            0,
            Sale::query()->count()
        );
    }

    private function environment(
        string $slug
    ): array {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant ' . $slug,
            'slug' => $slug,
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente ' . $slug,
        ]);

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

        $opportunity = Opportunity::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Oportunidade ' . $slug,
            'currency' => 'BRL',
            'value_minor' => 100000,
            'probability' => 100,
        ]);

        return [
            $tenant,
            $customer,
            $opportunity,
        ];
    }

    private function secondOpportunity(
        Tenant $tenant,
        Customer $customer,
        string $slug
    ): array {
        app(TenantContext::class)->set(
            $tenant
        );

        $pipeline = Pipeline::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pipeline ' . $slug,
            'is_default' => false,
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->create([
            'tenant_id' => $tenant->id,
            'pipeline_id' => $pipeline->id,
            'name' => 'Fechamento ' . $slug,
            'position' => 1,
            'is_active' => true,
        ]);

        $opportunity = Opportunity::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Oportunidade ' . $slug,
            'currency' => 'BRL',
            'value_minor' => 100000,
            'probability' => 100,
        ]);

        return [
            $tenant,
            $customer,
            $opportunity,
        ];
    }

    private function proposal(
        Tenant $tenant,
        Customer $customer,
        Opportunity $opportunity,
        string $status = 'accepted',
        int $totalMinor = 100000,
        string $currency = 'BRL'
    ): Proposal {
        app(TenantContext::class)->set(
            $tenant
        );

        return Proposal::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'number' => 'PROP-' . strtoupper(
                substr(
                    md5(
                        $tenant->slug
                        . '-'
                        . $opportunity->id
                        . '-'
                        . $status
                    ),
                    0,
                    8
                )
            ),
            'status' => $status,
            'currency' => $currency,
            'subtotal_minor' => $totalMinor,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => $totalMinor,
        ]);
    }
}
