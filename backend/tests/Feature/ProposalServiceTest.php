<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Services\ProposalService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProposalServiceTest extends TestCase
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

    private function catalog(
        array $overrides = []
    ): CatalogItem {
        return CatalogItem::create(
            array_merge(
                [
                    'type' => 'product',
                    'name' => 'Produto',
                    'code' => 'PROD-001',
                    'price_minor' => 10000,
                    'currency' => 'BRL',
                    'is_active' => true,
                ],
                $overrides
            )
        );
    }

    public function test_proposal_can_be_created_from_catalog_item(): void
    {
        $tenant = $this->tenant(
            'proposal-service-create'
        );

        $item = $this->catalog();

        $proposal = app(
            ProposalService::class
        )->create([
            'number' => 'PROP-001',
            'items' => [
                [
                    'catalog_item_id' => $item->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $this->assertSame(
            $tenant->id,
            $proposal->tenant_id
        );

        $this->assertSame(
            20000,
            $proposal->subtotal_minor
        );

        $this->assertSame(
            20000,
            $proposal->total_minor
        );

        $this->assertSame(
            'Produto',
            $proposal->items->first()->name
        );
    }

    public function test_proposal_calculates_discount_and_tax(): void
    {
        $this->tenant(
            'proposal-service-calc'
        );

        $item = $this->catalog([
            'price_minor' => 10000,
        ]);

        $proposal = app(
            ProposalService::class
        )->create([
            'number' => 'PROP-CALC',
            'items' => [
                [
                    'catalog_item_id' => $item->id,
                    'quantity' => 3,
                    'discount_minor' => 5000,
                    'taxes' => [
                        [
                            'code' => 'tax-a',
                            'amount_minor' => 1500,
                        ],
                        [
                            'code' => 'tax-b',
                            'amount_minor' => 500,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            30000,
            $proposal->subtotal_minor
        );

        $this->assertSame(
            5000,
            $proposal->discount_minor
        );

        $this->assertSame(
            2000,
            $proposal->tax_minor
        );

        $this->assertSame(
            27000,
            $proposal->total_minor
        );
    }

    public function test_catalog_snapshot_is_preserved(): void
    {
        $this->tenant(
            'proposal-service-snapshot'
        );

        $item = $this->catalog([
            'name' => 'Original',
            'price_minor' => 9000,
        ]);

        $proposal = app(
            ProposalService::class
        )->create([
            'number' => 'PROP-SNAPSHOT',
            'items' => [
                [
                    'catalog_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $item->update([
            'name' => 'Alterado',
            'price_minor' => 20000,
        ]);

        $snapshot = $proposal
            ->items()
            ->first();

        $this->assertSame(
            'Original',
            $snapshot->name
        );

        $this->assertSame(
            9000,
            $snapshot->unit_price_minor
        );
    }

    public function test_manual_item_is_supported(): void
    {
        $this->tenant(
            'proposal-service-manual'
        );

        $proposal = app(
            ProposalService::class
        )->create([
            'number' => 'PROP-MANUAL',
            'items' => [
                [
                    'item_type' => 'service',
                    'name' => 'Serviço manual',
                    'code' => 'MANUAL',
                    'quantity' => 1.5,
                    'unit_price_minor' => 10000,
                ],
            ],
        ]);

        $this->assertSame(
            15000,
            $proposal->total_minor
        );
    }

    public function test_partial_update_preserves_items(): void
    {
        $this->tenant(
            'proposal-service-partial'
        );

        $item = $this->catalog();

        $service = app(
            ProposalService::class
        );

        $proposal = $service->create([
            'number' => 'PROP-PARTIAL',
            'items' => [
                [
                    'catalog_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $updated = $service->update(
            $proposal,
            [
                'notes' => 'Atualizada',
            ]
        );

        $this->assertSame(
            1,
            $updated->items->count()
        );

        $this->assertSame(
            10000,
            $updated->total_minor
        );

        $this->assertSame(
            'Atualizada',
            $updated->notes
        );
    }

    public function test_items_can_be_replaced_on_update(): void
    {
        $this->tenant(
            'proposal-service-replace'
        );

        $item = $this->catalog();

        $service = app(
            ProposalService::class
        );

        $proposal = $service->create([
            'number' => 'PROP-REPLACE',
            'items' => [
                [
                    'catalog_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $updated = $service->update(
            $proposal,
            [
                'items' => [
                    [
                        'catalog_item_id' => $item->id,
                        'quantity' => 4,
                    ],
                ],
            ]
        );

        $this->assertSame(
            1,
            $updated->items->count()
        );

        $this->assertSame(
            40000,
            $updated->total_minor
        );
    }

    public function test_proposal_requires_at_least_one_item(): void
    {
        $this->tenant(
            'proposal-service-empty'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(ProposalService::class)
            ->create([
                'number' => 'PROP-EMPTY',
                'items' => [],
            ]);
    }

    public function test_discount_cannot_exceed_gross_value(): void
    {
        $this->tenant(
            'proposal-service-discount'
        );

        $item = $this->catalog();

        $this->expectException(
            RuntimeException::class
        );

        app(ProposalService::class)
            ->create([
                'number' => 'PROP-DISCOUNT',
                'items' => [
                    [
                        'catalog_item_id' => $item->id,
                        'quantity' => 1,
                        'discount_minor' => 10001,
                    ],
                ],
            ]);
    }

    public function test_catalog_item_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'proposal-service-a'
        );

        $this->tenant(
            'proposal-service-b'
        );

        $foreign = $this->catalog();

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(ProposalService::class)
            ->create([
                'number' => 'PROP-FOREIGN',
                'items' => [
                    [
                        'catalog_item_id' => $foreign->id,
                        'quantity' => 1,
                    ],
                ],
            ]);
    }

    public function test_customer_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'proposal-customer-a'
        );

        $this->tenant(
            'proposal-customer-b'
        );

        $foreign = Customer::create([
            'type' => 'company',
            'name' => 'Cliente estrangeiro',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(ProposalService::class)
            ->create([
                'number' => 'PROP-CUSTOMER',
                'customer_id' => $foreign->id,
                'items' => [
                    [
                        'item_type' => 'service',
                        'name' => 'Serviço',
                        'quantity' => 1,
                        'unit_price_minor' => 1000,
                    ],
                ],
            ]);
    }

    public function test_proposal_from_other_tenant_cannot_be_updated(): void
    {
        $tenantA = $this->tenant(
            'proposal-update-a'
        );

        $this->tenant(
            'proposal-update-b'
        );

        $proposal = app(
            ProposalService::class
        )->create([
            'number' => 'PROP-FOREIGN-UPDATE',
            'items' => [
                [
                    'item_type' => 'service',
                    'name' => 'Serviço',
                    'quantity' => 1,
                    'unit_price_minor' => 1000,
                ],
            ],
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(ProposalService::class)
            ->update(
                $proposal,
                [
                    'notes' => 'Tentativa',
                ]
            );
    }
}
