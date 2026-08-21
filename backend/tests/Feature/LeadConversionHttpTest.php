<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeadConversionHttpTest extends TestCase
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

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'name' => 'Lead Conversion User',
            'email' => $email,
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

    private function lead(
        Tenant $tenant
    ): Lead {
        app(TenantContext::class)->set(
            $tenant
        );

        return Lead::create([
            'name' => 'Lead HTTP',
            'email' => 'lead-http@example.com',
            'phone' => '(85) 99999-9999',
            'status' => 'qualified',
            'source' => 'website',
            'tags' => [
                'vip',
            ],
            'notes' => 'Lead para conversao.',
        ]);
    }

    public function test_lead_can_be_converted_through_http(): void
    {
        $tenant = $this->tenant(
            'lead-http'
        );

        $user = $this->user(
            $tenant,
            'lead-http@tenant.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        $lead = $this->lead(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://lead-http.localhost/leads/{$lead->id}/convert",
                [
                    'customer_type' => 'individual',
                ]
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = \App\Models\Customer::query()
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'customers.show',
                $customer->id
            )
        );

        $this->assertDatabaseHas(
            'leads',
            [
                'id' => $lead->id,
                'converted_customer_id' =>
                    $customer->id,
            ]
        );
    }

    public function test_company_conversion_through_http_is_supported(): void
    {
        $tenant = $this->tenant(
            'lead-http-company'
        );

        $user = $this->user(
            $tenant,
            'company@tenant.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        $lead = $this->lead(
            $tenant
        );

        $this
            ->actingAs($user)
            ->post(
                "http://lead-http-company.localhost/leads/{$lead->id}/convert",
                [
                    'customer_type' => 'company',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'customers',
            [
                'tenant_id' => $tenant->id,
                'type' => 'company',
                'name' => 'Lead HTTP',
            ]
        );
    }

    public function test_conversion_requires_leads_update_permission(): void
    {
        $tenant = $this->tenant(
            'lead-http-permission'
        );

        $user = $this->user(
            $tenant,
            'permission@tenant.local'
        );

        $lead = $this->lead(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://lead-http-permission.localhost/leads/{$lead->id}/convert",
                [
                    'customer_type' => 'individual',
                ]
            );

        $response->assertForbidden();
    }

    public function test_conversion_requires_leads_feature(): void
    {
        $tenant = $this->tenant(
            'lead-http-leads-feature'
        );

        $user = $this->user(
            $tenant,
            'leads-feature@tenant.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        $lead = $this->lead(
            $tenant
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://lead-http-leads-feature.localhost/leads/{$lead->id}/convert",
                [
                    'customer_type' => 'individual',
                ]
            );

        $response->assertForbidden();
    }

    public function test_conversion_requires_customers_feature(): void
    {
        $tenant = $this->tenant(
            'lead-http-customers-feature'
        );

        $user = $this->user(
            $tenant,
            'customers-feature@tenant.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        $lead = $this->lead(
            $tenant
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://lead-http-customers-feature.localhost/leads/{$lead->id}/convert",
                [
                    'customer_type' => 'individual',
                ]
            );

        $response->assertForbidden();
    }

    public function test_invalid_customer_type_is_rejected(): void
    {
        $tenant = $this->tenant(
            'lead-http-invalid-type'
        );

        $user = $this->user(
            $tenant,
            'invalid-type@tenant.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        $lead = $this->lead(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://lead-http-invalid-type.localhost/leads/{$lead->id}/convert",
                [
                    'customer_type' => 'invalid',
                ]
            );

        $response->assertSessionHasErrors(
            'customer_type'
        );
    }

    public function test_converted_lead_cannot_be_converted_again_through_http(): void
    {
        $tenant = $this->tenant(
            'lead-http-duplicate'
        );

        $user = $this->user(
            $tenant,
            'duplicate@tenant.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        $lead = $this->lead(
            $tenant
        );

        $url =
            "http://lead-http-duplicate.localhost/leads/{$lead->id}/convert";

        $this
            ->actingAs($user)
            ->post(
                $url,
                [
                    'customer_type' => 'individual',
                ]
            )
            ->assertRedirect();

        $response = $this
            ->actingAs($user)
            ->post(
                $url,
                [
                    'customer_type' => 'individual',
                ]
            );

        $response->assertSessionHasErrors(
            'lead'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            1,
            \App\Models\Customer::query()->count()
        );
    }

    public function test_other_tenant_lead_cannot_be_converted(): void
    {
        $tenantA = $this->tenant(
            'lead-http-a'
        );

        $tenantB = $this->tenant(
            'lead-http-b'
        );

        $userA = $this->user(
            $tenantA,
            'tenant-a@tenant.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::LEADS_UPDATE
        );

        $leadB = $this->lead(
            $tenantB
        );

        $response = $this
            ->actingAs($userA)
            ->post(
                "http://lead-http-a.localhost/leads/{$leadB->id}/convert",
                [
                    'customer_type' => 'individual',
                ]
            );

        $response->assertNotFound();
    }
}
