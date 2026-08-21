<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\PlatformAdmin;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformTenantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): PlatformAdmin
    {
        return PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => uniqid('platform-', true)
                . '@example.test',
            'password' => Hash::make(
                'SenhaSegura123'
            ),
            'is_active' => true,
        ]);
    }

    private function tenant(
        string $name,
        string $slug,
        string $status = 'active'
    ): Tenant {
        return Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => $status,
            'trial_started_at' =>
                '2026-08-10 12:00:00',
            'trial_ends_at' =>
                '2026-08-24 12:00:00',
        ]);
    }

    private function plan(
        string $code,
        string $name
    ): Plan {
        return Plan::query()->create([
            'code' => $code,
            'name' => $name,
            'active' => true,
        ]);
    }

    private function subscribe(
        Tenant $tenant,
        Plan $plan,
        SubscriptionStatus $status =
            SubscriptionStatus::ACTIVE
    ): Subscription {
        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'current_period_start' =>
                '2026-08-18 00:00:00',
            'current_period_end' =>
                '2026-09-18 00:00:00',
        ]);
    }

    public function test_platform_admin_can_list_tenants_globally(): void
    {
        $first = $this->tenant(
            'Empresa Global A',
            'empresa-global-a'
        );

        $second = $this->tenant(
            'Empresa Global B',
            'empresa-global-b',
            'blocked'
        );

        DB::table('users')->insert([
            [
                'tenant_id' => $first->id,
                'name' => 'Pessoa A',
                'email' => 'pessoa-a@example.test',
                'password' => Hash::make('secret'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $first->id,
                'name' => 'Pessoa B',
                'email' => 'pessoa-b@example.test',
                'password' => Hash::make('secret'),
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants'
            );

        $response
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee($first->name)
            ->assertSee($second->name)
            ->assertSee('empresa-global-a')
            ->assertSee('blocked')
            ->assertSee('2');
    }

    public function test_tenant_list_shows_latest_subscription_plan_and_trial(): void
    {
        $tenant = $this->tenant(
            'Tenant Plano Pro',
            'tenant-plano-pro'
        );

        $plan = $this->plan(
            'pro-global',
            'Pro Global'
        );

        $this->subscribe(
            $tenant,
            $plan
        );

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants'
            );

        $response
            ->assertOk()
            ->assertSee('Pro Global')
            ->assertSee('active')
            ->assertSee('24/08/2026');
    }

    public function test_platform_admin_can_view_tenant_details(): void
    {
        $tenant = $this->tenant(
            'Tenant Detalhado',
            'tenant-detalhado'
        );

        $plan = $this->plan(
            'business-global',
            'Business Global'
        );

        $this->subscribe(
            $tenant,
            $plan,
            SubscriptionStatus::SUSPENDED
        );

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 12,
        ]);

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' => UsageMetric::USERS,
            'limit_value' => 12,
        ]);

        DB::table('users')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'Pessoa Detalhe',
            'email' => 'detalhe@example.test',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants/'
                . $tenant->id
            );

        $response
            ->assertOk()
            ->assertSee('Tenant Detalhado')
            ->assertSee('tenant-detalhado')
            ->assertSee('Business Global')
            ->assertSee('suspended')
            ->assertSee('Features')
            ->assertSee('Limites do plano')
            ->assertSee('users')
            ->assertSee('12');
    }

    public function test_tenant_admin_cannot_access_global_tenant_management(): void
    {
        $tenant = $this->tenant(
            'Tenant Comum',
            'tenant-comum-global'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Tenant',
            'email' => 'tenant-admin-global@example.test',
            'password' => Hash::make(
                'SenhaSegura123'
            ),
            'role' => Role::ADMIN,
        ]);

        $this
            ->actingAs($user)
            ->get(
                'http://localhost/platform/tenants'
            )
            ->assertRedirect(
                route('platform.login')
            );

        $this
            ->actingAs($user)
            ->get(
                'http://localhost/platform/tenants/'
                . $tenant->id
            )
            ->assertRedirect(
                route('platform.login')
            );
    }

    public function test_platform_tenant_management_does_not_require_tenant_context(): void
    {
        $tenant = $this->tenant(
            'Tenant Sem Contexto',
            'tenant-sem-contexto'
        );

        app(TenantContext::class)->clear();

        $admin = $this->platformAdmin();

        $this
            ->actingAs($admin, 'platform')
            ->get(
                'http://localhost/platform/tenants'
            )
            ->assertOk();

        $this
            ->actingAs($admin, 'platform')
            ->get(
                'http://localhost/platform/tenants/'
                . $tenant->id
            )
            ->assertOk();


    }
}