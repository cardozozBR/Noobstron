<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProposalHttpTest extends TestCase
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
                Feature::PROPOSALS,
                true
            );
    }

    private function proposal(
        array $overrides = []
    ): Proposal {
        return Proposal::create(
            array_merge(
                [
                    'number' => 'PROP-HTTP',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'subtotal_minor' => 1000,
                    'discount_minor' => 0,
                    'tax_minor' => 0,
                    'total_minor' => 1000,
                ],
                $overrides
            )
        );
    }

    public function test_proposal_routes_require_authentication(): void
    {
        $tenant = $this->tenant(
            'proposal-http-auth'
        );

        $this->enable($tenant);

        $this->get(
            "http://{$tenant->slug}.localhost/proposals"
        )->assertRedirect();
    }

    public function test_index_requires_proposals_feature(): void
    {
        $tenant = $this->tenant(
            'proposal-http-feature'
        );

        $user = $this->user(
            $tenant,
            'feature-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_VIEW
        );

        app(TenantCapabilities::class)
            ->set(
                $tenant,
                Feature::PROPOSALS,
                false
            );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/proposals"
            )
            ->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        $tenant = $this->tenant(
            'proposal-http-permission'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'permission-user'
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/proposals"
            )
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_index(): void
    {
        $tenant = $this->tenant(
            'proposal-http-index'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'index-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_VIEW
        );

        $this->proposal([
            'number' => 'PROP-VISIBLE',
        ]);

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/proposals"
            )
            ->assertOk()
            ->assertSee('PROP-VISIBLE');
    }

    public function test_store_creates_proposal_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'proposal-http-store'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'store-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals",
                [
                    'number' => 'PROP-STORE',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Serviço HTTP',
                            'quantity' => 2,
                            'unit_price_minor' => 5000,
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('proposals.index')
            );

        $proposal = Proposal::query()
            ->where(
                'number',
                'PROP-STORE'
            )
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $proposal->tenant_id
        );

        $this->assertSame(
            10000,
            $proposal->total_minor
        );
    }

    public function test_proposal_from_other_tenant_cannot_be_edited(): void
    {
        $tenantA = $this->tenant(
            'proposal-http-a'
        );

        $this->enable($tenantA);

        $userA = $this->user(
            $tenantA,
            'tenant-a-user'
        );

        $this->grant(
            $userA,
            PermissionEnum::PROPOSALS_UPDATE
        );

        $tenantB = $this->tenant(
            'proposal-http-b'
        );

        $this->enable($tenantB);

        $foreign = $this->proposal([
            'number' => 'PROP-FOREIGN',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->get(
                "http://{$tenantA->slug}.localhost/proposals/{$foreign->id}/edit"
            )
            ->assertNotFound();
    }

    public function test_proposal_can_be_updated(): void
    {
        $tenant = $this->tenant(
            'proposal-http-update'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'update-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_UPDATE
        );

        $proposal = app(
            \App\Services\ProposalService::class
        )->create([
            'number' => 'PROP-BEFORE',
            'items' => [
                [
                    'item_type' => 'service',
                    'name' => 'Antes',
                    'quantity' => 1,
                    'unit_price_minor' => 1000,
                ],
            ],
        ]);

        $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}",
                [
                    'number' => 'PROP-AFTER',
                    'status' => 'sent',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Depois',
                            'quantity' => 2,
                            'unit_price_minor' => 2000,
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('proposals.index')
            );

        $this->assertDatabaseHas(
            'proposals',
            [
                'id' => $proposal->id,
                'number' => 'PROP-AFTER',
                'status' => 'sent',
                'total_minor' => 4000,
            ]
        );
    }

    public function test_delete_requires_delete_permission(): void
    {
        $tenant = $this->tenant(
            'proposal-http-delete'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'delete-user'
        );

        $proposal = $this->proposal();

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'proposals',
            [
                'id' => $proposal->id,
            ]
        );
    }

    public function test_store_supports_multiple_proposal_items(): void
    {
        $tenant = $this->tenant(
            'proposal-http-multiple'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'multiple-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals",
                [
                    'number' => 'PROP-MULTI',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Item A',
                            'quantity' => 2,
                            'unit_price_minor' => 1000,
                            'discount_minor' => 100,
                        ],
                        [
                            'item_type' => 'product',
                            'name' => 'Item B',
                            'quantity' => 3,
                            'unit_price_minor' => 2000,
                            'taxes' => [
                                [
                                    'code' => 'tax',
                                    'amount_minor' => 500,
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('proposals.index')
            );

        $proposal = Proposal::query()
            ->where('number', 'PROP-MULTI')
            ->firstOrFail();

        $this->assertSame(
            2,
            $proposal->items()->count()
        );

        $this->assertSame(
            8000,
            $proposal->subtotal_minor
        );

        $this->assertSame(
            100,
            $proposal->discount_minor
        );

        $this->assertSame(
            500,
            $proposal->tax_minor
        );

        $this->assertSame(
            8400,
            $proposal->total_minor
        );
    }

    public function test_store_supports_catalog_and_manual_items_together(): void
    {
        $tenant = $this->tenant(
            'proposal-http-mixed'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'mixed-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_CREATE
        );

        $catalog = \App\Models\CatalogItem::create([
            'type' => 'product',
            'name' => 'Catalog Product',
            'code' => 'CAT-1',
            'price_minor' => 5000,
            'currency' => 'BRL',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals",
                [
                    'number' => 'PROP-MIXED',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'catalog_item_id' => $catalog->id,
                            'quantity' => 1,
                        ],
                        [
                            'item_type' => 'service',
                            'name' => 'Manual Service',
                            'quantity' => 2,
                            'unit_price_minor' => 1000,
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('proposals.index')
            );

        $proposal = Proposal::query()
            ->where('number', 'PROP-MIXED')
            ->firstOrFail();

        $this->assertSame(
            2,
            $proposal->items()->count()
        );

        $this->assertSame(
            7000,
            $proposal->total_minor
        );
    }
    public function test_proposal_can_be_marked_as_accepted(): void
    {
        $tenant = $this->tenant(
            'proposal-http-accepted'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'proposal-accepted@local'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_UPDATE
        );

        $proposal = $this->proposal();

        $response = $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}",
                [
                    'number' => $proposal->number,
                    'status' => 'accepted',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Servico aceito',
                            'quantity' => 1,
                            'unit_price_minor' => 1000,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            "http://{$tenant->slug}.localhost/proposals"
        );

        $this->assertDatabaseHas(
            'proposals',
            [
                'id' => $proposal->id,
                'status' => 'accepted',
            ]
        );
    }

    public function test_proposal_can_be_marked_as_rejected(): void
    {
        $tenant = $this->tenant(
            'proposal-http-rejected'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'proposal-rejected@local'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_UPDATE
        );

        $proposal = $this->proposal();

        $response = $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}",
                [
                    'number' => $proposal->number,
                    'status' => 'rejected',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Servico recusado',
                            'quantity' => 1,
                            'unit_price_minor' => 1000,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            "http://{$tenant->slug}.localhost/proposals"
        );

        $this->assertDatabaseHas(
            'proposals',
            [
                'id' => $proposal->id,
                'status' => 'rejected',
            ]
        );
    }
}
