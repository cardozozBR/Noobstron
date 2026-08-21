<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\CatalogItem;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

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

    private function user(
        Tenant $tenant,
        string $name
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $tenant->slug
                . '-'
                . str($name)->slug()
                . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where(
                'name',
                $permission->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching(
                $model->id
            );
    }

    private function enable(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)
            ->set(
                $tenant,
                Feature::CATALOG,
                true
            );
    }

    private function item(
        array $overrides = []
    ): CatalogItem {
        return CatalogItem::create(
            array_merge(
                [
                    'type' => 'product',
                    'name' => 'Produto HTTP',
                    'code' => 'HTTP-001',
                    'price_minor' => 10000,
                    'currency' => 'BRL',
                    'is_active' => true,
                ],
                $overrides
            )
        );
    }

    public function test_catalog_routes_require_authentication(): void
    {
        $tenant = $this->tenant(
            'catalog-http-auth'
        );

        $this->enable($tenant);

        $this->get(
            "http://{$tenant->slug}.localhost/catalog"
        )
            ->assertRedirect();
    }

    public function test_index_requires_catalog_feature(): void
    {
        $tenant = $this->tenant(
            'catalog-http-feature'
        );

        $user = $this->user(
            $tenant,
            'feature-user'
        );

        $this->grant(
            $user,
            PermissionEnum::CATALOG_VIEW
        );

        app(TenantCapabilities::class)
            ->set(
                $tenant,
                Feature::CATALOG,
                false
            );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/catalog"
            )
            ->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        $tenant = $this->tenant(
            'catalog-http-permission'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'permission-user'
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/catalog"
            )
            ->assertForbidden();
    }

    public function test_user_with_feature_and_permission_can_access_index(): void
    {
        $tenant = $this->tenant(
            'catalog-http-index'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'index-user'
        );

        $this->grant(
            $user,
            PermissionEnum::CATALOG_VIEW
        );

        $this->item([
            'name' => 'Produto Visível',
        ]);

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/catalog"
            )
            ->assertOk()
            ->assertSee('Produto Visível');
    }

    public function test_store_creates_item_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'catalog-http-store'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'store-user'
        );

        $this->grant(
            $user,
            PermissionEnum::CATALOG_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/catalog",
                [
                    'type' => 'service',
                    'name' => 'Consultoria HTTP',
                    'code' => 'CONS-HTTP',
                    'price_minor' => 15000,
                    'currency' => 'BRL',
                    'is_active' => true,
                ]
            )
            ->assertRedirect(
                route('catalog.index')
            );

        $item = CatalogItem::query()
            ->where(
                'code',
                'CONS-HTTP'
            )
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $item->tenant_id
        );
    }

    public function test_item_from_other_tenant_cannot_be_edited(): void
    {
        $tenantA = $this->tenant(
            'catalog-http-a'
        );

        $this->enable($tenantA);

        $userA = $this->user(
            $tenantA,
            'tenant-a-user'
        );

        $this->grant(
            $userA,
            PermissionEnum::CATALOG_UPDATE
        );

        $tenantB = $this->tenant(
            'catalog-http-b'
        );

        $this->enable($tenantB);

        $foreign = $this->item([
            'code' => 'FOREIGN-HTTP',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->get(
                "http://{$tenantA->slug}.localhost/catalog/{$foreign->id}/edit"
            )
            ->assertNotFound();
    }

    public function test_item_can_be_updated(): void
    {
        $tenant = $this->tenant(
            'catalog-http-update'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'update-user'
        );

        $this->grant(
            $user,
            PermissionEnum::CATALOG_UPDATE
        );

        $item = $this->item();

        $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/catalog/{$item->id}",
                [
                    'type' => 'product',
                    'name' => 'Produto Atualizado HTTP',
                    'code' => 'HTTP-001',
                    'price_minor' => 20000,
                    'currency' => 'BRL',
                    'is_active' => false,
                ]
            )
            ->assertRedirect(
                route('catalog.index')
            );

        $this->assertDatabaseHas(
            'catalog_items',
            [
                'id' => $item->id,
                'tenant_id' => $tenant->id,
                'name' => 'Produto Atualizado HTTP',
                'price_minor' => 20000,
                'is_active' => false,
            ]
        );
    }

    public function test_delete_requires_delete_permission(): void
    {
        $tenant = $this->tenant(
            'catalog-http-delete'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'delete-user'
        );

        $item = $this->item();

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/catalog/{$item->id}"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'catalog_items',
            [
                'id' => $item->id,
            ]
        );
    }
}
