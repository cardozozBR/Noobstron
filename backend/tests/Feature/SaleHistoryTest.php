<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaleHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_sales_index_displays_closing_history(): void
    {
        [
            $tenant,
            $opportunity,
            $user,
        ] = $this->environment(
            'sale-history'
        );

        $this->actingAs($user);

        $sale = app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'number' =>
                        'SALE-HISTORY-001',
                ]
            );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/sales"
            );

        $response
            ->assertOk()
            ->assertSee(
                __('sales.history.title')
            )
            ->assertSee(
                'sale.closed'
            )
            ->assertSee(
                $sale->number
            )
            ->assertSee(
                $opportunity->name
            );
    }

    public function test_sales_history_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $opportunityA,
            $userA,
        ] = $this->environment(
            'sale-history-a'
        );

        $this->actingAs($userA);

        app(SaleService::class)->close(
            $opportunityA,
            [
                'number' =>
                    'SALE-TENANT-A',
            ]
        );

        [
            $tenantB,
            $opportunityB,
            $userB,
        ] = $this->environment(
            'sale-history-b'
        );

        $this->actingAs($userB);

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

        $response = $this
            ->actingAs($userA)
            ->get(
                "http://{$tenantA->slug}.localhost/sales"
            );

        $response
            ->assertOk()
            ->assertSee(
                'SALE-TENANT-A'
            )
            ->assertDontSee(
                'SALE-TENANT-B'
            );
    }

    public function test_sales_history_requires_view_permission(): void
    {
        [
            $tenant,
            ,
            $user,
        ] = $this->environment(
            'sale-history-permission',
            false
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/sales"
            )
            ->assertForbidden();
    }

    private function environment(
        string $slug,
        bool $grantView = true
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

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::SALES,
            true
        );

        $user = User::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' =>
                'Sales History User',
            'email' =>
                $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        if ($grantView) {
            $permission = Permission::query()
                ->where(
                    'name',
                    PermissionEnum::SALES_VIEW->value
                )
                ->firstOrFail();

            $user->permissions()
                ->syncWithoutDetaching([
                    $permission->id,
                ]);
        }

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
            $opportunity,
            $user,
        ];
    }
}