<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialIndicatorHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_financial_indicator_route_requires_authentication(): void
    {
        $tenant = $this->tenant(
            'financial-http-auth'
        );

        $this
            ->get(
                $this->url(
                    $tenant
                )
            )
            ->assertRedirect();
    }

    public function test_financial_indicator_route_requires_feature(): void
    {
        $tenant = $this->tenant(
            'financial-http-feature'
        );

        $user = $this->user(
            $tenant,
            'financial-feature@local'
        );

        $this->grant(
            $user
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::FINANCIAL_INDICATORS,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant
                )
            )
            ->assertForbidden();
    }

    public function test_financial_indicator_route_requires_permission(): void
    {
        $tenant = $this->tenant(
            'financial-http-permission'
        );

        $user = $this->user(
            $tenant,
            'financial-permission@local'
        );

        $this->enable(
            $tenant
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant
                )
            )
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_financial_indicators(): void
    {
        $tenant = $this->tenant(
            'financial-http-index'
        );

        $customer = $this->customer(
            $tenant,
            'Financial Customer'
        );

        Carbon::setTestNow(
            '2026-08-16 10:00:00'
        );

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' =>
                $customer->id,
            'title' =>
                'Financial Revenue',
            'amount_minor' =>
                125000,
            'due_date' =>
                '2026-08-16',
        ]);

        app(
            ReceivableService::class
        )->markPaid(
            $receivable
        );

        Carbon::setTestNow();

        $user = $this->user(
            $tenant,
            'financial-index@local'
        );

        $this->enable(
            $tenant
        );

        $this->grant(
            $user
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '?from=2026-08-01&until=2026-08-31'
                )
            )
            ->assertOk()
            ->assertSee(
                __('financial_indicators.title')
            )
            ->assertSee(
                '1.250,00'
            )
            ->assertSee(
                'Financial Customer'
            );
    }

    public function test_financial_indicator_data_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'financial-http-a'
        );

        $customerA = $this->customer(
            $tenantA,
            'Tenant A Customer'
        );

        $receivableA = app(
            ReceivableService::class
        )->create([
            'customer_id' =>
                $customerA->id,
            'title' =>
                'Tenant A Revenue',
            'amount_minor' =>
                100000,
            'due_date' =>
                '2026-08-10',
        ]);

        app(
            ReceivableService::class
        )->markPaid(
            $receivableA
        );

        $userA = $this->user(
            $tenantA,
            'financial-a@local'
        );

        $this->enable(
            $tenantA
        );

        $this->grant(
            $userA
        );

        $tenantB = $this->tenant(
            'financial-http-b'
        );

        $customerB = $this->customer(
            $tenantB,
            'Tenant B Secret'
        );

        $receivableB = app(
            ReceivableService::class
        )->create([
            'customer_id' =>
                $customerB->id,
            'title' =>
                'Tenant B Revenue',
            'amount_minor' =>
                900000,
            'due_date' =>
                '2026-08-10',
        ]);

        app(
            ReceivableService::class
        )->markPaid(
            $receivableB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->get(
                $this->url(
                    $tenantA,
                    '?from=2026-01-01&until=2026-12-31'
                )
            )
            ->assertOk()
            ->assertSee(
                'Tenant A Customer'
            )
            ->assertDontSee(
                'Tenant B Secret'
            );
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $tenant = $this->tenant(
            'financial-http-range'
        );

        $user = $this->user(
            $tenant,
            'financial-range@local'
        );

        $this->enable(
            $tenant
        );

        $this->grant(
            $user
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '?from=2026-09-01&until=2026-08-01'
                )
            )
            ->assertSessionHasErrors(
                'until'
            );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' =>
                'Tenant ' . $slug,
            'slug' =>
                $slug,
            'status' =>
                'active',
            'country_code' =>
                'BR',
            'locale' =>
                'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' =>
                'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    private function customer(
        Tenant $tenant,
        string $name
    ): Customer {
        app(TenantContext::class)->set(
            $tenant
        );

        return Customer::query()->create([
            'tenant_id' =>
                $tenant->id,
            'type' =>
                'company',
            'name' =>
                $name,
        ]);
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
            'name' =>
                'Financial Indicator User',
            'email' =>
                $email,
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' =>
                'user',
        ]);
    }

    private function enable(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::FINANCIAL_INDICATORS,
            true
        );
    }

    private function grant(
        User $user
    ): void {
        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::FINANCIAL_INDICATORS_VIEW->value
            )
            ->firstOrFail();

        $user
            ->permissions()
            ->syncWithoutDetaching([
                $permission->id,
            ]);
    }

    private function url(
        Tenant $tenant,
        string $suffix = ''
    ): string {
        return 'http://'
            . $tenant->slug
            . '.localhost/financial-indicators'
            . $suffix;
    }
}