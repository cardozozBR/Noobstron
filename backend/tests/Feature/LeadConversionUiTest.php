<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeadConversionUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function environment(
        string $slug = 'conversion-ui'
    ): array {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            true
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            true
        );

        $user = User::create([
            'name' => 'Conversion UI',
            'email' => $slug . '@tenant.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);

        foreach ([
            PermissionEnum::LEADS_VIEW,
            PermissionEnum::LEADS_UPDATE,
        ] as $permission) {
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

        $lead = Lead::create([
            'name' => 'Lead Conversion UI',
            'email' => 'ui@example.com',
            'status' => 'qualified',
            'source' => 'manual',
        ]);

        return [
            $tenant,
            $user,
            $lead,
        ];
    }

    public function test_conversion_form_is_visible(): void
    {
        [$tenant, $user, $lead] =
            $this->environment();

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/leads/{$lead->id}/edit"
            );

        $response->assertOk();

        $response->assertSee(
            route(
                'leads.convert',
                $lead->id
            ),
            false
        );

        $response->assertSee(
            'name="customer_type"',
            false
        );
    }

    public function test_conversion_edit_is_forbidden_without_update_permission(): void
    {
        [$tenant, $user, $lead] =
            $this->environment(
                'conversion-ui-permission'
            );

        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::LEADS_UPDATE->value
            )
            ->firstOrFail();

        $user->permissions()->detach(
            $permission->id
        );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/leads/{$lead->id}/edit"
            );

        $response->assertForbidden();
    }

    public function test_conversion_form_is_hidden_without_customers_feature(): void
    {
        [$tenant, $user, $lead] =
            $this->environment(
                'conversion-ui-feature'
            );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/leads/{$lead->id}/edit"
            );

        $response->assertOk();

        $response->assertDontSee(
            route(
                'leads.convert',
                $lead->id
            ),
            false
        );
    }

    public function test_converted_lead_shows_customer_link_instead_of_form(): void
    {
        [$tenant, $user, $lead] =
            $this->environment(
                'conversion-ui-converted'
            );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead,
            CustomerType::INDIVIDUAL
        );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/leads/{$lead->id}/edit"
            );

        $response->assertOk();

        $response->assertSee(
            route(
                'customers.show',
                $customer->id
            ),
            false
        );

        $response->assertDontSee(
            'name="customer_type"',
            false
        );
    }

    public function test_japanese_conversion_ui_uses_tenant_locale(): void
    {
        [$tenant, $user, $lead] =
            $this->environment(
                'conversion-ui-ja'
            );

        $tenant->update([
            'locale' => 'ja',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/leads/{$lead->id}/edit"
            );

        $response->assertOk();

        $response->assertSee(
            '顧客に変換',
            false
        );

        $response->assertSee(
            '顧客タイプ',
            false
        );
    }
}
