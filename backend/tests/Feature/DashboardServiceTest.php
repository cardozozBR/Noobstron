<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Pipeline $pipeline;

    private PipelineStage $stage;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::query()->create([
            'name' => 'Tenant Dashboard',
            'slug' => 'tenant-dashboard',
        ]);

        app(TenantContext::class)->set($this->tenant);

        $this->customer = Customer::query()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'company',
            'name' => 'Cliente Dashboard',
        ]);

        $this->pipeline = Pipeline::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pipeline Dashboard',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->stage = PipelineStage::query()->create([
            'tenant_id' => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Proposta',
            'position' => 1,
            'is_active' => true,
        ]);
    }

    private function tenant(string $slug): Tenant
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant ' . $slug,
            'slug' => $slug,
        ]);

        app(TenantContext::class)->set($tenant);

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $name
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $tenant->slug
                . '-'
                . str($name)->slug()
                . '@local',
            'password' => 'TesteSenha123',
            'role' => 'user',
        ]);
    }

    private function opportunityEnvironment(
        Tenant $tenant
    ): array {
        app(TenantContext::class)->set($tenant);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente ' . $tenant->slug,
        ]);

        $pipeline = Pipeline::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pipeline ' . $tenant->slug,
            'is_default' => true,
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->create([
            'tenant_id' => $tenant->id,
            'pipeline_id' => $pipeline->id,
            'name' => 'Proposta',
            'position' => 1,
            'is_active' => true,
        ]);

        return [
            $customer,
            $pipeline,
            $stage,
        ];
    }
    public function test_dashboard_calculates_opportunity_metrics(): void
    {
        $this->createOpportunity(
            valueMinor: 100000,
            probability: 50
        );

        $this->createOpportunity(
            valueMinor: 200000,
            probability: 25
        );

        $metrics = app(DashboardService::class)->metrics();

        $this->assertSame(
            2,
            $metrics['total_opportunities']
        );

        $this->assertSame(
            300000,
            $metrics['pipeline_value_minor']
        );

        $this->assertSame(
            100000,
            $metrics['weighted_pipeline_value_minor']
        );
    }

    public function test_dashboard_calculates_activity_metrics(): void
    {
        $this->createActivity(
            dueAt: now()->subHour()
        );

        $this->createActivity(
            dueAt: now()->addHours(12)
        );

        $this->createActivity(
            dueAt: now()->addDays(3)
        );

        $metrics = app(DashboardService::class)->metrics();

        $this->assertSame(
            3,
            $metrics['pending_activities']
        );

        $this->assertSame(
            1,
            $metrics['overdue_activities']
        );

        $this->assertSame(
            1,
            $metrics['due_soon_activities']
        );
    }

    public function test_dashboard_groups_opportunities_by_stage(): void
    {
        $this->createOpportunity(
            valueMinor: 100000
        );

        $this->createOpportunity(
            valueMinor: 250000
        );

        $stages = app(DashboardService::class)
            ->opportunitiesByStage();

        $stage = $stages->firstWhere(
            'id',
            $this->stage->id
        );

        $this->assertNotNull($stage);

        $this->assertSame(
            2,
            $stage->opportunities_count
        );

        $this->assertSame(
            350000,
            (int) $stage->opportunities_sum_value_minor
        );
    }

    public function test_dashboard_returns_upcoming_activities_in_order(): void
    {
        $later = $this->createActivity(
            title: 'Depois',
            dueAt: now()->addHours(8)
        );

        $earlier = $this->createActivity(
            title: 'Antes',
            dueAt: now()->addHours(2)
        );

        $activities = app(DashboardService::class)
            ->upcomingActivities();

        $this->assertCount(2, $activities);

        $this->assertSame(
            $earlier->id,
            $activities->first()->id
        );

        $this->assertSame(
            $later->id,
            $activities->last()->id
        );
    }

    public function test_dashboard_metrics_are_isolated_by_tenant(): void
    {
        $this->createOpportunity(
            valueMinor: 100000
        );

        $this->createActivity(
            dueAt: now()->addHours(2)
        );

        $otherTenant = Tenant::query()->create([
            'name' => 'Outro Tenant Dashboard',
            'slug' => 'outro-tenant-dashboard',
        ]);

        app(TenantContext::class)->set($otherTenant);

        $otherCustomer = Customer::query()->create([
            'tenant_id' => $otherTenant->id,
            'type' => 'company',
            'name' => 'Outro Cliente',
        ]);

        $otherPipeline = Pipeline::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Outro Pipeline',
            'is_default' => true,
            'is_active' => true,
        ]);

        $otherStage = PipelineStage::query()->create([
            'tenant_id' => $otherTenant->id,
            'pipeline_id' => $otherPipeline->id,
            'name' => 'Outro Stage',
            'position' => 1,
            'is_active' => true,
        ]);

        Opportunity::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Outra Oportunidade',
            'customer_id' => $otherCustomer->id,
            'pipeline_id' => $otherPipeline->id,
            'pipeline_stage_id' => $otherStage->id,
            'value_minor' => 900000,
            'currency' => 'BRL',
            'probability' => 100,
        ]);

        app(TenantContext::class)->set($this->tenant);

        $metrics = app(DashboardService::class)->metrics();

        $this->assertSame(
            1,
            $metrics['total_opportunities']
        );

        $this->assertSame(
            100000,
            $metrics['pipeline_value_minor']
        );

        $this->assertSame(
            1,
            $metrics['pending_activities']
        );
    }

    private function createOpportunity(
        int $valueMinor = 100000,
        int $probability = 50
    ): Opportunity {
        return Opportunity::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Oportunidade Dashboard',
            'customer_id' => $this->customer->id,
            'pipeline_id' => $this->pipeline->id,
            'pipeline_stage_id' => $this->stage->id,
            'value_minor' => $valueMinor,
            'currency' => 'BRL',
            'probability' => $probability,
        ]);
    }

    private function createActivity(
        string $title = 'Atividade Dashboard',
        mixed $dueAt = null
    ): Activity {
        return Activity::query()->create([
            'tenant_id' => $this->tenant->id,
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => $title,
            'due_at' => $dueAt,
        ]);
    }

    public function test_dashboard_calculates_lead_conversion_metrics(): void
    {
        $tenant = $this->tenant('dashboard-leads');

        Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead Aberto',
            'status' => 'new',
            'source' => 'manual',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente Convertido',
        ]);

        Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead Convertido',
            'status' => 'qualified',
            'source' => 'manual',
            'converted_customer_id' => $customer->id,
            'converted_at' => now(),
        ]);

        $metrics = app(DashboardService::class)
            ->metrics();

        $this->assertSame(
            2,
            $metrics['total_leads']
        );

        $this->assertSame(
            1,
            $metrics['converted_leads']
        );

        $this->assertSame(
            50.0,
            $metrics['lead_conversion_rate']
        );
    }

    public function test_dashboard_conversion_rate_is_zero_without_leads(): void
    {
        $this->tenant('dashboard-no-leads');

        $metrics = app(DashboardService::class)
            ->metrics();

        $this->assertSame(
            0,
            $metrics['total_leads']
        );

        $this->assertSame(
            0,
            $metrics['converted_leads']
        );

        $this->assertSame(
            0.0,
            $metrics['lead_conversion_rate']
        );
    }

    public function test_dashboard_groups_opportunities_by_responsible(): void
    {
        $tenant = $this->tenant(
            'dashboard-responsibles'
        );

        $responsibleA = $this->user(
            $tenant,
            'Responsavel A'
        );

        $responsibleB = $this->user(
            $tenant,
            'Responsavel B'
        );

        $unusedUser = $this->user(
            $tenant,
            'Sem Oportunidades'
        );

        [$customer, $pipeline, $stage] =
            $this->opportunityEnvironment(
                $tenant
            );

        Opportunity::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Venda A1',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'responsible_user_id' => $responsibleA->id,
            'value_minor' => 100000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);

        Opportunity::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Venda A2',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'responsible_user_id' => $responsibleA->id,
            'value_minor' => 200000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);

        Opportunity::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Venda B1',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'responsible_user_id' => $responsibleB->id,
            'value_minor' => 400000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);

        $rows = app(DashboardService::class)
            ->opportunitiesByResponsible();

        $this->assertCount(
            2,
            $rows
        );

        $rowA = $rows->firstWhere(
            'id',
            $responsibleA->id
        );

        $rowB = $rows->firstWhere(
            'id',
            $responsibleB->id
        );

        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);

        $this->assertSame(
            2,
            $rowA->assigned_opportunities_count
        );

        $this->assertSame(
            300000,
            (int) $rowA->assigned_opportunities_sum_value_minor
        );

        $this->assertSame(
            1,
            $rowB->assigned_opportunities_count
        );

        $this->assertSame(
            400000,
            (int) $rowB->assigned_opportunities_sum_value_minor
        );

        $this->assertNull(
            $rows->firstWhere(
                'id',
                $unusedUser->id
            )
        );
    }

    public function test_lead_and_responsible_metrics_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'dashboard-metrics-a'
        );

        $responsibleA = $this->user(
            $tenantA,
            'Owner A'
        );

        [$customerA, $pipelineA, $stageA] =
            $this->opportunityEnvironment(
                $tenantA
            );

        Lead::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Lead A',
            'status' => 'new',
            'source' => 'manual',
        ]);

        Opportunity::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Opportunity A',
            'customer_id' => $customerA->id,
            'pipeline_id' => $pipelineA->id,
            'pipeline_stage_id' => $stageA->id,
            'responsible_user_id' => $responsibleA->id,
            'value_minor' => 100000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);

        $tenantB = $this->tenant(
            'dashboard-metrics-b'
        );

        $responsibleB = $this->user(
            $tenantB,
            'Owner B'
        );

        [$customerB, $pipelineB, $stageB] =
            $this->opportunityEnvironment(
                $tenantB
            );

        Lead::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Lead B 1',
            'status' => 'new',
            'source' => 'manual',
        ]);

        Lead::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Lead B 2',
            'status' => 'new',
            'source' => 'manual',
        ]);

        Opportunity::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Opportunity B',
            'customer_id' => $customerB->id,
            'pipeline_id' => $pipelineB->id,
            'pipeline_stage_id' => $stageB->id,
            'responsible_user_id' => $responsibleB->id,
            'value_minor' => 900000,
            'currency' => 'BRL',
            'probability' => 100,
        ]);

        app(\App\Services\TenantContext::class)
            ->set($tenantA);

        $service = app(DashboardService::class);

        $metrics = $service->metrics();
        $responsibles =
            $service->opportunitiesByResponsible();

        $this->assertSame(
            1,
            $metrics['total_leads']
        );

        $this->assertCount(
            1,
            $responsibles
        );

        $this->assertSame(
            $responsibleA->id,
            $responsibles->first()->id
        );
    }}
