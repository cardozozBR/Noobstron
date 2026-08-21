<?php

namespace Tests\Feature;

use App\Enums\CatalogItemType;
use App\Models\CatalogItem;
use App\Models\Tenant;
use App\Services\CatalogService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class CatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug,
        string $currency = 'BRL'
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => $currency,
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    private function service(): CatalogService
    {
        return app(
            CatalogService::class
        );
    }

    private function createItem(
        array $overrides = []
    ): CatalogItem {
        return $this->service()->create(
            array_merge(
                [
                    'type' => 'product',
                    'name' => 'Produto padrão',
                    'code' => 'SKU-001',
                    'price_minor' => 10000,
                    'currency' => 'BRL',
                ],
                $overrides
            )
        );
    }

    public function test_catalog_item_can_be_created(): void
    {
        $tenant = $this->tenant(
            'catalog-service-create'
        );

        $item = $this->service()->create([
            'type' => 'product',
            'name' => 'Notebook',
            'code' => 'NOTE-001',
            'price_minor' => 350000,
            'currency' => 'brl',
        ]);

        $this->assertSame(
            $tenant->id,
            $item->tenant_id
        );

        $this->assertSame(
            CatalogItemType::PRODUCT,
            $item->type
        );

        $this->assertSame(
            'Notebook',
            $item->name
        );

        $this->assertSame(
            350000,
            $item->price_minor
        );

        $this->assertSame(
            'BRL',
            $item->currency
        );

        $this->assertTrue(
            $item->is_active
        );
    }

    public function test_product_and_service_can_be_created(): void
    {
        $this->tenant(
            'catalog-service-types'
        );

        $product = $this->createItem([
            'code' => 'P-1',
        ]);

        $service = $this->createItem([
            'type' => 'service',
            'name' => 'Consultoria',
            'code' => 'S-1',
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

    public function test_text_fields_are_normalized(): void
    {
        $this->tenant(
            'catalog-service-normalization'
        );

        $item = $this->createItem([
            'name' => '  Implantação  ',
            'code' => '  SERV-100  ',
            'currency' => 'brl',
        ]);

        $this->assertSame(
            'Implantação',
            $item->name
        );

        $this->assertSame(
            'SERV-100',
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
            'catalog-service-null-code'
        );

        $item = $this->createItem([
            'code' => '   ',
        ]);

        $this->assertNull(
            $item->code
        );
    }

    public function test_tenant_currency_is_default(): void
    {
        $this->tenant(
            'catalog-service-default-currency',
            'USD'
        );

        $item = $this->service()->create([
            'type' => 'service',
            'name' => 'Consulting',
            'price_minor' => 5000,
        ]);

        $this->assertSame(
            'USD',
            $item->currency
        );
    }

    public function test_partial_update_preserves_existing_values(): void
    {
        $this->tenant(
            'catalog-service-partial'
        );

        $item = $this->createItem([
            'name' => 'Produto Inicial',
            'code' => 'INITIAL',
            'price_minor' => 25000,
        ]);

        $updated = $this->service()->update(
            $item,
            [
                'name' => 'Produto Atualizado',
            ]
        );

        $this->assertSame(
            'Produto Atualizado',
            $updated->name
        );

        $this->assertSame(
            'INITIAL',
            $updated->code
        );

        $this->assertSame(
            25000,
            $updated->price_minor
        );

        $this->assertSame(
            'BRL',
            $updated->currency
        );
    }

    public function test_item_can_be_deactivated_and_activated(): void
    {
        $this->tenant(
            'catalog-service-active'
        );

        $item = $this->createItem();

        $inactive = $this->service()
            ->deactivate($item);

        $this->assertFalse(
            $inactive->is_active
        );

        $active = $this->service()
            ->activate($inactive);

        $this->assertTrue(
            $active->is_active
        );
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $this->tenant(
            'catalog-service-code'
        );

        $this->createItem([
            'code' => 'DUPLICATE',
        ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->createItem([
            'name' => 'Outro',
            'code' => 'DUPLICATE',
        ]);
    }

    public function test_item_can_keep_its_code_on_update(): void
    {
        $this->tenant(
            'catalog-service-keep-code'
        );

        $item = $this->createItem([
            'code' => 'KEEP',
        ]);

        $updated = $this->service()->update(
            $item,
            [
                'code' => 'KEEP',
                'name' => 'Atualizado',
            ]
        );

        $this->assertSame(
            'KEEP',
            $updated->code
        );
    }

    public function test_same_code_can_exist_between_tenants(): void
    {
        $this->tenant(
            'catalog-service-code-a'
        );

        $this->createItem([
            'code' => 'SHARED',
        ]);

        $this->tenant(
            'catalog-service-code-b'
        );

        $item = $this->createItem([
            'code' => 'SHARED',
        ]);

        $this->assertSame(
            'SHARED',
            $item->code
        );
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->tenant(
            'catalog-service-invalid-type'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->createItem([
            'type' => 'invalid',
        ]);
    }

    public function test_negative_price_is_rejected(): void
    {
        $this->tenant(
            'catalog-service-negative'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->createItem([
            'price_minor' => -1,
        ]);
    }

    public function test_invalid_price_format_is_rejected(): void
    {
        $this->tenant(
            'catalog-service-price-format'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->createItem([
            'price_minor' => '10.50',
        ]);
    }

    public function test_invalid_currency_is_rejected(): void
    {
        $this->tenant(
            'catalog-service-currency'
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->createItem([
            'currency' => 'XXX',
        ]);
    }

    public function test_item_from_other_tenant_cannot_be_updated(): void
    {
        $tenantA = $this->tenant(
            'catalog-service-a'
        );

        $this->tenant(
            'catalog-service-b'
        );

        $foreign = $this->createItem([
            'code' => 'FOREIGN',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        $this->service()->update(
            $foreign,
            [
                'name' => 'Tentativa',
            ]
        );
    }
}
