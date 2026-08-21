<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaleHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_sales_routes_require_authentication(): void
    {
        [$tenant, , $opportunity] =
            $this->environment('sales-auth');

        $this->get(
            $this->url(
                $tenant,
                '/sales'
            )
        )->assertRedirect();

        $this->get(
            $this->url(
                $tenant,
                "/opportunities/{$opportunity->id}/close-sale"
            )
        )->assertRedirect();

        $this->post(
            $this->url(
                $tenant,
                "/opportunities/{$opportunity->id}/close-sale"
            )
        )->assertRedirect();
    }

    public function test_index_requires_sales_feature(): void
    {
        [$tenant] =
            $this->environment('sales-feature');

        $user = $this->user(
            $tenant,
            'feature@local'
        );

        $this->grant(
            $user,
            'sales.view'
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::SALES,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/sales'
                )
            )
            ->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        [$tenant] =
            $this->environment('sales-view');

        $user = $this->user(
            $tenant,
            'view@local'
        );

        $this->enableSales($tenant);

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/sales'
                )
            )
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_index(): void
    {
        [$tenant] =
            $this->environment('sales-index');

        $user = $this->user(
            $tenant,
            'index@local'
        );

        $this->enableSales($tenant);
        $this->grant(
            $user,
            'sales.view'
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/sales'
                )
            )
            ->assertOk()
            ->assertSee('Vendas');
    }

    public function test_create_requires_create_permission(): void
    {
        [$tenant, , $opportunity] =
            $this->environment(
                'sales-create-permission'
            );

        $user = $this->user(
            $tenant,
            'create-permission@local'
        );

        $this->enableSales($tenant);

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    "/opportunities/{$opportunity->id}/close-sale"
                )
            )
            ->assertForbidden();
    }

    public function test_direct_close_creates_sale(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment(
                'sales-direct'
            );

        $user = $this->user(
            $tenant,
            'direct@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.create'
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    "/opportunities/{$opportunity->id}/close-sale"
                ),
                [
                    'number' =>
                        ' sale-http-001 ',
                    'total_minor' =>
                        125000,
                    'currency' =>
                        'USD',
                ]
            )
            ->assertRedirect(
                route('sales.index')
            );

        $sale = Sale::query()
            ->firstOrFail();

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
            'SALE-HTTP-001',
            $sale->number
        );

        $this->assertSame(
            125000,
            $sale->total_minor
        );
    }

    public function test_accepted_proposal_can_close_sale(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment(
                'sales-proposal'
            );

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity,
            'accepted',
            175000,
            'EUR'
        );

        $user = $this->user(
            $tenant,
            'proposal@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.create'
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    "/opportunities/{$opportunity->id}/close-sale"
                ),
                [
                    'proposal_id' =>
                        $proposal->id,
                    'total_minor' =>
                        1,
                    'currency' =>
                        'BRL',
                ]
            )
            ->assertRedirect(
                route('sales.index')
            );

        $sale = Sale::query()
            ->firstOrFail();

        $this->assertSame(
            $proposal->id,
            $sale->proposal_id
        );

        $this->assertSame(
            175000,
            $sale->total_minor
        );

        $this->assertSame(
            'EUR',
            $sale->currency
        );
    }

    public function test_non_accepted_proposal_does_not_close_sale(): void
    {
        [$tenant, $customer, $opportunity] =
            $this->environment(
                'sales-draft'
            );

        $proposal = $this->proposal(
            $tenant,
            $customer,
            $opportunity,
            'draft'
        );

        $user = $this->user(
            $tenant,
            'draft@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.create'
        );

        $url = $this->url(
            $tenant,
            "/opportunities/{$opportunity->id}/close-sale"
        );

        $this
            ->actingAs($user)
            ->from($url)
            ->post(
                $url,
                [
                    'proposal_id' =>
                        $proposal->id,
                ]
            )
            ->assertRedirect()
            ->assertSessionHasErrors(
                'sale'
            );

        $this->assertSame(
            0,
            Sale::query()->count()
        );
    }

    public function test_duplicate_close_is_rejected(): void
    {
        [$tenant, , $opportunity] =
            $this->environment(
                'sales-duplicate'
            );

        $user = $this->user(
            $tenant,
            'duplicate@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.create'
        );

        $url = $this->url(
            $tenant,
            "/opportunities/{$opportunity->id}/close-sale"
        );

        $this
            ->actingAs($user)
            ->post($url)
            ->assertRedirect(
                route('sales.index')
            );

        $this
            ->actingAs($user)
            ->from($url)
            ->post($url)
            ->assertRedirect()
            ->assertSessionHasErrors(
                'sale'
            );

        $this->assertSame(
            1,
            Sale::query()->count()
        );
    }

    public function test_other_tenant_opportunity_cannot_be_closed(): void
    {
        [$tenantA] =
            $this->environment(
                'sales-tenant-a'
            );

        [, , $opportunityB] =
            $this->environment(
                'sales-tenant-b'
            );

        $userA = $this->user(
            $tenantA,
            'tenant-a@local'
        );

        $this->enableSales($tenantA);

        $this->grant(
            $userA,
            'sales.create'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->post(
                $this->url(
                    $tenantA,
                    "/opportunities/{$opportunityB->id}/close-sale"
                )
            )
            ->assertNotFound();
    }

    public function test_index_is_isolated_between_tenants(): void
    {
        [$tenantA, , $opportunityA] =
            $this->environment(
                'sales-index-a'
            );

        $userA = $this->user(
            $tenantA,
            'index-a@local'
        );

        $this->enableSales($tenantA);

        $this->grant(
            $userA,
            'sales.view'
        );

        app(SaleService::class)->close(
            $opportunityA,
            [
                'number' =>
                    'SALE-TENANT-A',
            ]
        );

        [, , $opportunityB] =
            $this->environment(
                'sales-index-b'
            );

        app(SaleService::class)->close(
            $opportunityB,
            [
                'number' =>
                    'SALE-TENANT-B',
            ]
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->get(
                $this->url(
                    $tenantA,
                    '/sales'
                )
            )
            ->assertOk()
            ->assertSee(
                'SALE-TENANT-A'
            )
            ->assertDontSee(
                'SALE-TENANT-B'
            );
    }

    private function environment(
        string $slug
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

        $opportunity = Opportunity::query()->create([
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
            'value_minor' => 100000,
            'probability' => 100,
        ]);

        return [
            $tenant,
            $customer,
            $opportunity,
        ];
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' => 'Sales User',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function enableSales(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::SALES,
            true
        );
    }

    private function grant(
        User $user,
        string $name
    ): void {
        $permission = Permission::query()
            ->where(
                'name',
                $name
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching([
                $permission->id,
            ]);
    }

    private function proposal(
        Tenant $tenant,
        Customer $customer,
        Opportunity $opportunity,
        string $status,
        int $totalMinor = 100000,
        string $currency = 'BRL'
    ): Proposal {
        app(TenantContext::class)->set(
            $tenant
        );

        return Proposal::query()->create([
            'tenant_id' =>
                $tenant->id,
            'customer_id' =>
                $customer->id,
            'opportunity_id' =>
                $opportunity->id,
            'number' =>
                'PROP-' . strtoupper(
                    substr(
                        md5(
                            $tenant->slug
                            . '-'
                            . $status
                            . '-'
                            . $opportunity->id
                        ),
                        0,
                        8
                    )
                ),
            'status' => $status,
            'currency' => $currency,
            'subtotal_minor' =>
                $totalMinor,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' =>
                $totalMinor,
        ]);
    }

    private function url(
        Tenant $tenant,
        string $path
    ): string {
        return 'http://'
            . $tenant->slug
            . '.localhost'
            . $path;
    }

    public function test_sales_index_uses_tenant_locale(): void
    {
        [$tenant] =
            $this->environment(
                'sales-locale-en'
            );

        $tenant->update([
            'locale' => 'en',
        ]);

        $user = $this->user(
            $tenant,
            'sales-locale@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.view'
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/sales'
                )
            )
            ->assertOk()
            ->assertSee('Sales')
            ->assertSee(
                'Review the history of closed sales.'
            );
    }

    public function test_sales_close_form_uses_translations(): void
    {
        [$tenant, , $opportunity] =
            $this->environment(
                'sales-close-i18n'
            );

        $user = $this->user(
            $tenant,
            'sales-close-i18n@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.create'
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    "/opportunities/{$opportunity->id}/close-sale"
                )
            )
            ->assertOk()
            ->assertSee(
                __('sales.close_title')
            )
            ->assertSee(
                __('sales.register')
            );
    }
    public function test_opportunity_index_shows_close_sale_action(): void
    {
        [$tenant, , $opportunity] =
            $this->environment(
                'sales-opportunity-action'
            );

        $user = $this->user(
            $tenant,
            'sales-opportunity-action@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.create'
        );

        $this->grant(
            $user,
            'opportunities.view'
        );

        app(
            \App\Support\TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::OPPORTUNITIES,
            true
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/opportunities'
                )
            )
            ->assertOk()
            ->assertSee(
                __('sales.close_action')
            );
    }

    public function test_closed_opportunity_shows_sale_state(): void
    {
        [$tenant, , $opportunity] =
            $this->environment(
                'sales-opportunity-closed'
            );

        $user = $this->user(
            $tenant,
            'sales-opportunity-closed@local'
        );

        $this->enableSales($tenant);

        $this->grant(
            $user,
            'sales.create'
        );

        $this->grant(
            $user,
            'opportunities.view'
        );

        app(
            \App\Support\TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::OPPORTUNITIES,
            true
        );

        $sale = app(
            \App\Services\SaleService::class
        )->close(
            $opportunity,
            [
                'number' =>
                    'SALE-CLOSED-UI',
            ]
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/opportunities'
                )
            )
            ->assertOk()
            ->assertSee(
                __('sales.closed_badge')
            )
            ->assertSee(
                $sale->number
            );
    }}
