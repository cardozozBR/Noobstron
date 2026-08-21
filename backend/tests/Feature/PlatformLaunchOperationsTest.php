<?php

namespace Tests\Feature;

use App\Models\CommercialContact;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlatformAdmin;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformLaunchOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_dashboard_exposes_subscription_trial_revenue_and_usage_overview(): void
    {
        $admin = $this->platformAdmin();

        $plan = Plan::query()->create([
            'code' => 'launch-plan',
            'name' => 'Launch Plan',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => 19900,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Launch Tenant',
            'slug' => 'launch-tenant',
            'status' => 'active',
            'currency' => 'BRL',
            'trial_started_at' => now()->subDay(),
            'trial_ends_at' => now()->addDays(4),
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform')
            ->assertOk()
            ->assertSee('Assinaturas ativas')
            ->assertSee('Trials vencendo em 7 dias')
            ->assertSee('MRR contratual')
            ->assertSee('BRL 199,00')
            ->assertSee('Uso global');
    }

    public function test_platform_admin_can_view_operational_health_and_commercial_contacts(): void
    {
        $admin = $this->platformAdmin();

        CommercialContact::query()->create([
            'name' => 'Lead Comercial',
            'email' => 'lead@example.test',
            'message' => 'Quero uma demonstração.',
            'status' => 'new',
        ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform/health')
            ->assertOk()
            ->assertSee('Saúde operacional');

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform/contacts')
            ->assertOk()
            ->assertSee('Lead Comercial')
            ->assertSee('lead@example.test');
    }

    private function platformAdmin(): PlatformAdmin
    {
        return PlatformAdmin::query()->create([
            'name' => 'Launch Master',
            'email' => 'launch-master@example.test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }
}
