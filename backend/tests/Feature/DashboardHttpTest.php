<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionBillingService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function tenant(string $slug): Tenant
    {
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

        return $tenant;
    }

    private function user(Tenant $tenant, string $name): User
    {
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => "{$name}@local",
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where('name', $permission->value)
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching($model->id);
    }

    private function dashboard(
        Tenant $tenant,
        User $user
    ) {
        return $this
            ->actingAs($user)
            ->get("http://{$tenant->slug}.localhost/dashboard");
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $tenant = $this->tenant('dashboard-access');
        $user = $this->user($tenant, 'dashboard-access-user');

        $this->dashboard($tenant, $user)
            ->assertOk();
    }

    public function test_opportunity_permission_shows_sales_metrics(): void
    {
        $tenant = $this->tenant('dashboard-opportunities');
        $user = $this->user($tenant, 'dashboard-opportunities-user');

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::OPPORTUNITIES,
            true
        );

        $this->grant(
            $user,
            PermissionEnum::OPPORTUNITIES_VIEW
        );

        $this->dashboard($tenant, $user)
            ->assertOk()
            ->assertSee(__('ui.dashboard.open_opportunities'))
            ->assertSee(__('ui.dashboard.pipeline_by_stage'))
            ->assertDontSee(__('ui.dashboard.upcoming_activities'));
    }

    public function test_activity_permission_shows_activity_metrics(): void
    {
        $tenant = $this->tenant('dashboard-activities');
        $user = $this->user($tenant, 'dashboard-activities-user');

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::ACTIVITIES,
            true
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_VIEW
        );

        $this->dashboard($tenant, $user)
            ->assertOk()
            ->assertSee(__('ui.dashboard.pending_activities'))
            ->assertSee(__('ui.dashboard.upcoming_activities'))
            ->assertDontSee(__('ui.dashboard.pipeline_by_stage'));
    }

    public function test_user_without_crm_permissions_does_not_see_commercial_dashboard(): void
    {
        $tenant = $this->tenant('dashboard-no-permission');
        $user = $this->user($tenant, 'dashboard-no-permission-user');

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::OPPORTUNITIES,
            true
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::ACTIVITIES,
            true
        );

        $this->dashboard($tenant, $user)
            ->assertOk()
            ->assertDontSee(__('ui.dashboard.commercial_overview'))
            ->assertDontSee(__('ui.dashboard.pipeline_by_stage'))
            ->assertDontSee(__('ui.dashboard.upcoming_activities'));
    }

    public function test_disabled_opportunity_feature_hides_sales_dashboard(): void
    {
        $tenant = $this->tenant('dashboard-opportunities-disabled');
        $user = $this->user(
            $tenant,
            'dashboard-opportunities-disabled-user'
        );

        $this->grant(
            $user,
            PermissionEnum::OPPORTUNITIES_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::OPPORTUNITIES,
            false
        );

        $this->dashboard($tenant, $user)
            ->assertOk()
            ->assertDontSee(__('ui.dashboard.pipeline_by_stage'));
    }

    public function test_disabled_activity_feature_hides_activity_dashboard(): void
    {
        $tenant = $this->tenant('dashboard-activities-disabled');
        $user = $this->user(
            $tenant,
            'dashboard-activities-disabled-user'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::ACTIVITIES,
            false
        );

        $this->dashboard($tenant, $user)
            ->assertOk()
            ->assertDontSee(__('ui.dashboard.upcoming_activities'));
    }

    public function test_dashboard_does_not_render_other_tenant_data(): void
    {
        $tenantA = $this->tenant('dashboard-tenant-a');
        $userA = $this->user(
            $tenantA,
            'dashboard-tenant-a-user'
        );

        app(TenantCapabilities::class)->set(
            $tenantA,
            Feature::OPPORTUNITIES,
            true
        );

        $this->grant(
            $userA,
            PermissionEnum::OPPORTUNITIES_VIEW
        );

        $customerA = \App\Models\Customer::query()->create([
            'tenant_id' => $tenantA->id,
            'type' => 'company',
            'name' => 'Cliente Tenant A',
        ]);

        $pipelineA = \App\Models\Pipeline::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Pipeline Tenant A',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stageA = \App\Models\PipelineStage::query()->create([
            'tenant_id' => $tenantA->id,
            'pipeline_id' => $pipelineA->id,
            'name' => 'Etapa Tenant A',
            'position' => 1,
            'is_active' => true,
        ]);

        \App\Models\Opportunity::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Oportunidade Tenant A',
            'customer_id' => $customerA->id,
            'pipeline_id' => $pipelineA->id,
            'pipeline_stage_id' => $stageA->id,
            'value_minor' => 100000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);

        $tenantB = $this->tenant('dashboard-tenant-b');

        app(TenantCapabilities::class)->set(
            $tenantB,
            Feature::OPPORTUNITIES,
            true
        );

        $customerB = \App\Models\Customer::query()->create([
            'tenant_id' => $tenantB->id,
            'type' => 'company',
            'name' => 'Cliente Tenant B',
        ]);

        $pipelineB = \App\Models\Pipeline::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Pipeline Tenant B',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stageB = \App\Models\PipelineStage::query()->create([
            'tenant_id' => $tenantB->id,
            'pipeline_id' => $pipelineB->id,
            'name' => 'Etapa Tenant B',
            'position' => 1,
            'is_active' => true,
        ]);

        \App\Models\Opportunity::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Oportunidade Tenant B',
            'customer_id' => $customerB->id,
            'pipeline_id' => $pipelineB->id,
            'pipeline_stage_id' => $stageB->id,
            'value_minor' => 900000,
            'currency' => 'BRL',
            'probability' => 100,
        ]);

        app(\App\Services\TenantContext::class)
            ->set($tenantA);

        $response = $this->dashboard(
            $tenantA,
            $userA
        );

        $response
            ->assertOk()
            ->assertSee('Etapa Tenant A')
            ->assertDontSee('Etapa Tenant B');
    }
    public function test_lead_permission_shows_lead_and_conversion_metrics(): void
    {
        $tenant = $this->tenant(
            'dashboard-leads-visible'
        );

        $user = $this->user(
            $tenant,
            'dashboard-leads-visible-user'
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            true
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        \App\Models\Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead Aberto Dashboard',
            'status' => 'new',
            'source' => 'manual',
        ]);

        $customer = \App\Models\Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente Convertido Dashboard',
        ]);

        \App\Models\Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead Convertido Dashboard',
            'status' => 'qualified',
            'source' => 'manual',
            'converted_customer_id' => $customer->id,
            'converted_at' => now(),
        ]);

        $this->dashboard(
            $tenant,
            $user
        )
            ->assertOk()
            ->assertSee(
                __('ui.dashboard.leads')
            )
            ->assertSee(
                __('ui.dashboard.converted_leads')
            )
            ->assertSee(
                __('ui.dashboard.conversion_rate')
            )
            ->assertSee('2')
            ->assertSee('50.00%');
    }

    public function test_disabled_lead_feature_hides_lead_metrics(): void
    {
        $tenant = $this->tenant(
            'dashboard-leads-disabled'
        );

        $user = $this->user(
            $tenant,
            'dashboard-leads-disabled-user'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            false
        );

        $this->dashboard(
            $tenant,
            $user
        )
            ->assertOk()
            ->assertDontSee(
                __('ui.dashboard.leads_description')
            )
            ->assertDontSee(
                __('ui.dashboard.conversion_rate')
            );
    }

    public function test_dashboard_shows_opportunities_grouped_by_responsible(): void
    {
        $tenant = $this->tenant(
            'dashboard-responsible-visible'
        );

        $viewer = $this->user(
            $tenant,
            'dashboard-responsible-viewer'
        );

        $responsible = $this->user(
            $tenant,
            'Responsavel Comercial Dashboard'
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::OPPORTUNITIES,
            true
        );

        $this->grant(
            $viewer,
            PermissionEnum::OPPORTUNITIES_VIEW
        );

        $customer = \App\Models\Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente Responsavel Dashboard',
        ]);

        $pipeline = \App\Models\Pipeline::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pipeline Responsavel Dashboard',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stage = \App\Models\PipelineStage::query()->create([
            'tenant_id' => $tenant->id,
            'pipeline_id' => $pipeline->id,
            'name' => 'Negociacao Dashboard',
            'position' => 1,
            'is_active' => true,
        ]);

        \App\Models\Opportunity::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Opportunity Owner Dashboard',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'responsible_user_id' => $responsible->id,
            'value_minor' => 250000,
            'currency' => 'BRL',
            'probability' => 75,
        ]);

        $this->dashboard(
            $tenant,
            $viewer
        )
            ->assertOk()
            ->assertSee(
                __('ui.dashboard.opportunities_by_responsible')
            )
            ->assertSee(
                'Responsavel Comercial Dashboard'
            )
            ->assertSee(
                __('ui.dashboard.value')
            );
    }
    public function test_unverified_user_is_redirected_from_dashboard_to_verification_notice(): void
    {
        $tenant = $this->tenant(
            'dashboard-unverified'
        );

        $user = $this->user(
            $tenant,
            'dashboard-unverified-user'
        );

        $user->forceFill([
            'email_verified_at' => null,
        ])->save();

        $this->assertNull(
            $user->fresh()->email_verified_at
        );

        $response = $this->dashboard(
            $tenant,
            $user
        );

        $response->assertRedirect(
            route('verification.notice')
        );
    }

    public function test_dashboard_shows_trial_cta_for_unpaid_trial(): void
    {
        $tenant = $this->tenant('dashboard-trial-cta');
        $user = $this->user($tenant, 'dashboard-trial-cta-user');

        $tenant->forceFill([
            'trial_started_at' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'trial_ends_at' =>
                CarbonImmutable::parse(
                    '2026-08-30 00:00:00 UTC'
                ),
        ])->save();

        $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-23 12:00:00 UTC'
            )
        );

        try {
            $this->dashboard($tenant, $user)
                ->assertOk()
                ->assertSee('Período de teste ativo')
                ->assertSee('Ver planos');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_shows_manage_subscription_for_active_paid_subscription(): void
    {
        $tenant = $this->tenant('dashboard-active-cta');
        $user = $this->user($tenant, 'dashboard-active-cta-user');

        $subscription = $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        app(SubscriptionBillingService::class)
            ->markPaid(
                $subscription,
                'stripe',
                'sub_dashboard_active',
                'card',
            );

        $this->dashboard($tenant, $user)
            ->assertOk()
            ->assertSee('Assinatura ativa')
            ->assertSee(
                __('billing.manage_subscription')
            );
    }

    public function test_dashboard_shows_resubscribe_for_cancelled_paid_subscription_even_with_trial_dates(): void
    {
        $tenant = $this->tenant('dashboard-cancelled-cta');
        $user = $this->user(
            $tenant,
            'dashboard-cancelled-cta-user'
        );

        $tenant->forceFill([
            'trial_started_at' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'trial_ends_at' =>
                CarbonImmutable::parse(
                    '2026-09-06 00:00:00 UTC'
                ),
        ])->save();

        $subscription = $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        app(SubscriptionBillingService::class)
            ->markPaid(
                $subscription,
                'stripe',
                'sub_dashboard_cancelled',
                'card',
            );

        $subscription->forceFill([
            'status' => SubscriptionStatus::CANCELLED,
        ])->save();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-23 12:00:00 UTC'
            )
        );

        try {
            $this->dashboard($tenant, $user)
                ->assertOk()
                ->assertSee('Assinatura inativa')
                ->assertSee('Assinar novamente')
                ->assertDontSee('Período de teste ativo');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function subscription(
        Tenant $tenant,
        SubscriptionStatus $status,
    ): Subscription {
        $plan = Plan::query()->create([
            'code' => $tenant->slug . '-plan',
            'name' => 'Start',
            'active' => true,
        ]);

        app(TenantContext::class)->set($tenant);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-01 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-01 00:00:00 UTC'
                ),
        ]);
    }

}