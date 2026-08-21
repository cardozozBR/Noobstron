<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\Sale;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_is_created_in_current_tenant(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-alpha');

        $sale = $this->sale(
            $tenant,
            $customer,
            $opportunity
        );

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
    }

    public function test_sale_has_expected_casts(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-casts');

        $sale = $this->sale(
            $tenant,
            $customer,
            $opportunity
        );

        $this->assertIsInt(
            $sale->total_minor
        );

        $this->assertNotNull(
            $sale->closed_at
        );
    }

    public function test_sale_relationships_work(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-relations');

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity
        );

        $sale = $this->sale(
            $tenant,
            $customer,
            $opportunity,
            $proposal
        );

        $this->assertTrue(
            $sale->tenant->is($tenant)
        );

        $this->assertTrue(
            $sale->customer->is($customer)
        );

        $this->assertTrue(
            $sale->opportunity->is($opportunity)
        );

        $this->assertTrue(
            $sale->proposal->is($proposal)
        );
    }

    public function test_sale_queries_are_isolated_by_tenant(): void
    {
        [$tenantA, $customerA, $opportunityA] =
            $this->environment('sale-query-a');

        $saleA = $this->sale(
            $tenantA,
            $customerA,
            $opportunityA
        );

        [$tenantB, $customerB, $opportunityB] =
            $this->environment('sale-query-b');

        $this->sale(
            $tenantB,
            $customerB,
            $opportunityB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            [$saleA->id],
            Sale::query()
                ->pluck('id')
                ->all()
        );
    }

    public function test_sale_from_other_tenant_cannot_be_found(): void
    {
        [$tenantA] =
            $this->environment('sale-find-a');

        [$tenantB, $customerB, $opportunityB] =
            $this->environment('sale-find-b');

        $foreign = $this->sale(
            $tenantB,
            $customerB,
            $opportunityB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            Sale::query()->find(
                $foreign->id
            )
        );
    }

    public function test_opportunity_can_only_have_one_sale(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-unique-opportunity');

        $this->sale(
            $tenant,
            $customer,
            $opportunity
        );

        $this->expectException(
            QueryException::class
        );

        Sale::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'number' => 'SALE-002',
            'currency' => 'BRL',
            'total_minor' => 200000,
            'closed_at' => now(),
            'customer_name' => $customer->name,
            'opportunity_title' => $opportunity->name,
        ]);
    }

    public function test_sale_number_can_repeat_between_tenants(): void
    {
        [$tenantA, $customerA, $opportunityA] =
            $this->environment('sale-number-a');

        $saleA = $this->sale(
            $tenantA,
            $customerA,
            $opportunityA
        );

        [$tenantB, $customerB, $opportunityB] =
            $this->environment('sale-number-b');

        $saleB = $this->sale(
            $tenantB,
            $customerB,
            $opportunityB
        );

        $this->assertSame(
            $saleA->number,
            $saleB->number
        );
    }

    public function test_parent_models_have_sale_relations(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment('sale-parents');

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity
        );

        $sale = $this->sale(
            $tenant,
            $customer,
            $opportunity,
            $proposal
        );

        $this->assertTrue(
            $tenant->sales()
                ->whereKey($sale->id)
                ->exists()
        );

        $this->assertTrue(
            $customer->sales()
                ->whereKey($sale->id)
                ->exists()
        );

        $this->assertTrue(
            $opportunity->sales()
                ->whereKey($sale->id)
                ->exists()
        );

        $this->assertTrue(
            $proposal->sales()
                ->whereKey($sale->id)
                ->exists()
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

    private function proposal(
        Tenant $tenant,
        Customer $customer,
        Opportunity $opportunity
    ): Proposal {
        app(TenantContext::class)->set(
            $tenant
        );

        return Proposal::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'number' => 'PROP-' . $tenant->slug,
            'status' => 'accepted',
            'currency' => 'BRL',
            'subtotal_minor' => 100000,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 100000,
        ]);
    }

    private function sale(
        Tenant $tenant,
        Customer $customer,
        Opportunity $opportunity,
        ?Proposal $proposal = null
    ): Sale {
        app(TenantContext::class)->set(
            $tenant
        );

        return Sale::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'proposal_id' => $proposal?->id,
            'number' => 'SALE-001',
            'currency' => 'BRL',
            'total_minor' => 100000,
            'closed_at' => now(),
            'customer_name' => $customer->name,
            'opportunity_title' => $opportunity->name,
            'proposal_number' => $proposal?->number,
        ]);
    }
}
