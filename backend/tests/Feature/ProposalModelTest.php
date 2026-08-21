<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalModelTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
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

        return $tenant;
    }

    private function customer(): Customer
    {
        return Customer::create([
            'type' => 'company',
            'name' => 'Cliente Proposta',
        ]);
    }

    private function opportunity(
        Customer $customer
    ): Opportunity {
        $pipeline = Pipeline::create([
            'name' => 'Pipeline Proposta',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Proposta',
            'position' => 1,
            'is_active' => true,
        ]);

        return Opportunity::create([
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Oportunidade Proposta',
            'value_minor' => 100000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);
    }

    private function proposal(
        array $overrides = []
    ): Proposal {
        return Proposal::create(
            array_merge(
                [
                    'number' => 'PROP-001',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'subtotal_minor' => 100000,
                    'discount_minor' => 10000,
                    'tax_minor' => 5000,
                    'total_minor' => 95000,
                ],
                $overrides
            )
        );
    }

    public function test_proposal_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'proposal-create'
        );

        $proposal = $this->proposal();

        $this->assertSame(
            $tenant->id,
            $proposal->tenant_id
        );
    }

    public function test_proposal_status_is_cast_to_enum(): void
    {
        $this->tenant(
            'proposal-status'
        );

        $proposal = $this->proposal();

        $this->assertSame(
            ProposalStatus::DRAFT,
            $proposal->status
        );
    }

    public function test_proposal_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'proposal-a'
        );

        $this->proposal([
            'number' => 'A-001',
        ]);

        $tenantB = $this->tenant(
            'proposal-b'
        );

        $this->proposal([
            'number' => 'B-001',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            ['A-001'],
            Proposal::query()
                ->pluck('number')
                ->all()
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            ['B-001'],
            Proposal::query()
                ->pluck('number')
                ->all()
        );
    }

    public function test_proposal_can_reference_customer_and_opportunity(): void
    {
        $this->tenant(
            'proposal-relations'
        );

        $customer = $this->customer();
        $opportunity = $this->opportunity(
            $customer
        );

        $proposal = $this->proposal([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
        ]);

        $this->assertTrue(
            $proposal->customer->is(
                $customer
            )
        );

        $this->assertTrue(
            $proposal->opportunity->is(
                $opportunity
            )
        );
    }

    public function test_proposal_has_ordered_items(): void
    {
        $this->tenant(
            'proposal-items'
        );

        $proposal = $this->proposal();

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'position' => 2,
            'item_type' => 'service',
            'name' => 'Segundo',
            'quantity' => 1,
            'unit_price_minor' => 2000,
            'total_minor' => 2000,
        ]);

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'position' => 1,
            'item_type' => 'product',
            'name' => 'Primeiro',
            'quantity' => 1,
            'unit_price_minor' => 1000,
            'total_minor' => 1000,
        ]);

        $this->assertSame(
            [
                'Primeiro',
                'Segundo',
            ],
            $proposal
                ->items()
                ->pluck('name')
                ->all()
        );
    }

    public function test_proposal_item_can_snapshot_catalog_item(): void
    {
        $this->tenant(
            'proposal-snapshot'
        );

        $catalog = CatalogItem::create([
            'type' => 'product',
            'name' => 'Produto Original',
            'code' => 'SKU-ORIGINAL',
            'price_minor' => 5000,
            'currency' => 'BRL',
            'is_active' => true,
        ]);

        $proposal = $this->proposal();

        $item = ProposalItem::create([
            'proposal_id' => $proposal->id,
            'catalog_item_id' => $catalog->id,
            'position' => 1,
            'item_type' => $catalog->type->value,
            'name' => $catalog->name,
            'code' => $catalog->code,
            'quantity' => 2,
            'unit_price_minor' => 5000,
            'discount_minor' => 1000,
            'tax_minor' => 500,
            'total_minor' => 9500,
            'taxes' => [
                [
                    'code' => 'generic',
                    'amount_minor' => 500,
                ],
            ],
        ]);

        $catalog->update([
            'name' => 'Produto Alterado',
            'price_minor' => 9999,
        ]);

        $item->refresh();

        $this->assertSame(
            'Produto Original',
            $item->name
        );

        $this->assertSame(
            5000,
            $item->unit_price_minor
        );

        $this->assertSame(
            500,
            $item->taxes[0]['amount_minor']
        );
    }

    public function test_proposal_item_quantity_must_be_positive(): void
    {
        $this->tenant(
            'proposal-quantity'
        );

        $proposal = $this->proposal();

        $this->expectException(
            \RuntimeException::class
        );

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'position' => 1,
            'item_type' => 'product',
            'name' => 'Inválido',
            'quantity' => 0,
            'unit_price_minor' => 1000,
            'total_minor' => 1000,
        ]);
    }

    public function test_proposal_number_can_repeat_between_tenants(): void
    {
        $this->tenant(
            'proposal-number-a'
        );

        $this->proposal([
            'number' => 'PROP-SHARED',
        ]);

        $this->tenant(
            'proposal-number-b'
        );

        $proposal = $this->proposal([
            'number' => 'PROP-SHARED',
        ]);

        $this->assertSame(
            'PROP-SHARED',
            $proposal->number
        );
    }

    public function test_parent_models_have_proposal_relations(): void
    {
        $tenant = $this->tenant(
            'proposal-parent-relations'
        );

        $customer = $this->customer();

        $opportunity = $this->opportunity(
            $customer
        );

        $proposal = $this->proposal([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
        ]);

        $this->assertTrue(
            $tenant->proposals()
                ->whereKey($proposal->id)
                ->exists()
        );

        $this->assertTrue(
            $customer->proposals()
                ->whereKey($proposal->id)
                ->exists()
        );

        $this->assertTrue(
            $opportunity->proposals()
                ->whereKey($proposal->id)
                ->exists()
        );
    }
}
