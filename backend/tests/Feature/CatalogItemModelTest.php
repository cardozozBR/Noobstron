<?php

namespace Tests\Feature;

use App\Enums\CatalogItemType;
use App\Models\CatalogItem;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use ValueError;
use Tests\TestCase;

class CatalogItemModelTest extends TestCase
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

    private function item(
        array $overrides = []
    ): CatalogItem {
        return CatalogItem::create(
            array_merge(
                [
                    'type' => 'product',
                    'name' => 'Produto padrÃ£o',
                    'code' => 'SKU-001',
                    'price_minor' => 150000,
                    'currency' => 'BRL',
                    'is_active' => true,
                ],
                $overrides
            )
        );
    }

    public function test_catalog_item_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'catalog-create'
        );

        $item = $this->item();

        $this->assertSame(
            $tenant->id,
            $item->tenant_id
        );
    }

    public function test_catalog_item_supports_product_and_service_types(): void
    {
        $this->tenant(
            'catalog-types'
        );

        $product = $this->item([
            'code' => 'PRODUCT-1',
        ]);

        $service = $this->item([
            'type' => 'service',
            'name' => 'Consultoria',
            'code' => 'SERVICE-1',
        ]);

        $this->assertSame(
            CatalogItemType::PRODUCT,
            $product->type
        );

        $this->assertSame(
            CatalogItemType::SERVICE,
            $service->type
        );
    }

    public function test_catalog_item_has_expected_casts(): void
    {
        $this->tenant(
            'catalog-casts'
        );

        $item = $this->item([
            'price_minor' => 12345,
            'is_active' => false,
        ]);

        $this->assertSame(
            12345,
            $item->price_minor
        );

        $this->assertFalse(
            $item->is_active
        );
    }

    public function test_catalog_item_normalizes_text_and_currency(): void
    {
        $this->tenant(
            'catalog-normalize'
        );

        $item = $this->item([
            'name' => '  Produto A  ',
            'code' => '  SKU-A  ',
            'currency' => 'brl',
        ]);

        $this->assertSame(
            'Produto A',
            $item->name
        );

        $this->assertSame(
            'SKU-A',
            $item->code
        );

        $this->assertSame(
            'BRL',
            $item->currency
        );
    }

    public function test_blank_code_becomes_null(): void
    {
        $this->tenant(
            'catalog-code-null'
        );

        $item = $this->item([
            'code' => '   ',
        ]);

        $this->assertNull(
            $item->code
        );
    }

    public function test_catalog_item_name_is_required(): void
    {
        $this->tenant(
            'catalog-name'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->item([
            'name' => '   ',
        ]);
    }

    public function test_catalog_item_type_must_be_valid(): void
    {
        $this->tenant(
            'catalog-invalid-type'
        );

        $this->expectException(
            ValueError::class
        );

        $this->item([
            'type' => 'invalid',
        ]);
    }

    public function test_catalog_item_price_cannot_be_negative(): void
    {
        $this->tenant(
            'catalog-negative-price'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->item([
            'price_minor' => -1,
        ]);
    }

    public function test_catalog_item_currency_must_be_supported(): void
    {
        $this->tenant(
            'catalog-invalid-currency'
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->item([
            'currency' => 'XXX',
        ]);
    }

    public function test_catalog_code_is_unique_inside_tenant(): void
    {
        $this->tenant(
            'catalog-code-unique'
        );

        $this->item([
            'code' => 'SAME-CODE',
        ]);

        $this->expectException(
            QueryException::class
        );

        $this->item([
            'name' => 'Outro item',
            'code' => 'SAME-CODE',
        ]);
    }

    public function test_same_catalog_code_can_exist_between_tenants(): void
    {
        $this->tenant(
            'catalog-code-a'
        );

        $this->item([
            'code' => 'SHARED-CODE',
        ]);

        $this->tenant(
            'catalog-code-b'
        );

        $second = $this->item([
            'code' => 'SHARED-CODE',
        ]);

        $this->assertSame(
            'SHARED-CODE',
            $second->code
        );
    }

    public function test_catalog_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'catalog-a'
        );

        $this->item([
            'name' => 'Item A',
            'code' => 'A',
        ]);

        $tenantB = $this->tenant(
            'catalog-b'
        );

        $this->item([
            'name' => 'Item B',
            'code' => 'B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            ['Item A'],
            CatalogItem::query()
                ->pluck('name')
                ->all()
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            ['Item B'],
            CatalogItem::query()
                ->pluck('name')
                ->all()
        );
    }

    public function test_catalog_item_from_other_tenant_cannot_be_found(): void
    {
        $tenantA = $this->tenant(
            'catalog-find-a'
        );

        $this->tenant(
            'catalog-find-b'
        );

        $foreign = $this->item();

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            CatalogItem::query()
                ->find($foreign->id)
        );
    }

    public function test_tenant_has_catalog_items_relation(): void
    {
        $tenant = $this->tenant(
            'catalog-relation'
        );

        $item = $this->item();

        $this->assertTrue(
            $tenant->catalogItems()
                ->whereKey($item->id)
                ->exists()
        );
    }
}
