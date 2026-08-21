<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\CustomerType;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OpportunityService;
use App\Services\PipelineService;
use App\Services\PipelineStageService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ActivityModelTest extends TestCase
{
    use RefreshDatabase;

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

        app(TenantContext::class)->set($tenant);

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Activity User',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);
    }

    private function customer(
        Tenant $tenant,
        string $name
    ): Customer {
        app(TenantContext::class)->set($tenant);

        return Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => $name,
        ]);
    }

    private function opportunity(
        Tenant $tenant,
        Customer $customer
    ) {
        app(TenantContext::class)->set($tenant);

        $pipeline = app(PipelineService::class)->create([
            'name' => 'Comercial',
        ]);

        $stage = app(PipelineStageService::class)->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        return app(OpportunityService::class)->create([
            'name' => 'Oportunidade teste',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 100000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);
    }

    public function test_activity_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant('activity-tenant');

        $activity = Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => 'Enviar proposta',
        ]);

        $this->assertSame(
            $tenant->id,
            $activity->tenant_id
        );
    }

    public function test_activity_has_expected_casts(): void
    {
        $this->tenant('activity-casts');

        $activity = Activity::create([
            'type' => ActivityType::CALL,
            'status' => ActivityStatus::PENDING,
            'title' => 'Ligar para cliente',
            'due_at' => '2026-08-20 10:00:00',
        ]);

        $this->assertSame(
            ActivityType::CALL,
            $activity->type
        );

        $this->assertSame(
            ActivityStatus::PENDING,
            $activity->status
        );

        $this->assertNotNull(
            $activity->due_at
        );
    }

    public function test_activity_supports_all_expected_types(): void
    {
        $this->tenant('activity-types');

        foreach (ActivityType::cases() as $type) {
            $activity = Activity::create([
                'type' => $type,
                'status' => ActivityStatus::PENDING,
                'title' => 'Atividade ' . $type->value,
            ]);

            $this->assertSame(
                $type,
                $activity->type
            );
        }
    }

    public function test_activity_relationships_work(): void
    {
        $tenant = $this->tenant(
            'activity-relations'
        );

        $customer = $this->customer(
            $tenant,
            'Cliente A'
        );

        $user = $this->user(
            $tenant,
            'activity-relations@local'
        );

        $opportunity = $this->opportunity(
            $tenant,
            $customer
        );

        $activity = Activity::create([
            'type' => ActivityType::MEETING,
            'status' => ActivityStatus::PENDING,
            'title' => 'Reuniao',
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'responsible_user_id' => $user->id,
        ]);

        $this->assertTrue(
            $activity->customer->is($customer)
        );

        $this->assertTrue(
            $activity->opportunity->is($opportunity)
        );

        $this->assertTrue(
            $activity->responsible->is($user)
        );
    }

    public function test_activity_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant('activity-a');

        Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => 'Tenant A',
        ]);

        $tenantB = $this->tenant('activity-b');

        Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => 'Tenant B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->assertSame(
            ['Tenant A'],
            Activity::query()
                ->pluck('title')
                ->all()
        );

        app(TenantContext::class)->set($tenantB);

        $this->assertSame(
            ['Tenant B'],
            Activity::query()
                ->pluck('title')
                ->all()
        );
    }

    public function test_customer_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'activity-customer-a'
        );

        $customer = $this->customer(
            $tenantA,
            'Cliente A'
        );

        $this->tenant(
            'activity-customer-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => 'Cross tenant customer',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_opportunity_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'activity-opportunity-a'
        );

        $customer = $this->customer(
            $tenantA,
            'Cliente A'
        );

        $opportunity = $this->opportunity(
            $tenantA,
            $customer
        );

        $this->tenant(
            'activity-opportunity-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        Activity::create([
            'type' => ActivityType::FOLLOW_UP,
            'status' => ActivityStatus::PENDING,
            'title' => 'Cross tenant opportunity',
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_responsible_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'activity-user-a'
        );

        $user = $this->user(
            $tenantA,
            'activity-user-a@local'
        );

        $this->tenant(
            'activity-user-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => 'Cross tenant responsible',
            'responsible_user_id' => $user->id,
        ]);
    }

    public function test_completed_status_sets_completed_at(): void
    {
        $this->tenant(
            'activity-complete'
        );

        $activity = Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::COMPLETED,
            'title' => 'Concluir atividade',
        ]);

        $this->assertNotNull(
            $activity->completed_at
        );
    }

    public function test_non_completed_status_clears_completed_at(): void
    {
        $this->tenant(
            'activity-reopen'
        );

        $activity = Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::COMPLETED,
            'title' => 'Atividade concluida',
        ]);

        $this->assertNotNull(
            $activity->completed_at
        );

        $activity->status =
            ActivityStatus::PENDING;

        $activity->save();

        $this->assertNull(
            $activity->fresh()->completed_at
        );
    }

    public function test_parent_models_have_activity_relations(): void
    {
        $tenant = $this->tenant(
            'activity-parent-relations'
        );

        $customer = $this->customer(
            $tenant,
            'Cliente parent'
        );

        $user = $this->user(
            $tenant,
            'activity-parent@local'
        );

        $opportunity = $this->opportunity(
            $tenant,
            $customer
        );

        $activity = Activity::create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => 'Parent relations',
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'responsible_user_id' => $user->id,
        ]);

        $this->assertTrue(
            $tenant->activities()
                ->whereKey($activity->id)
                ->exists()
        );

        $this->assertTrue(
            $customer->activities()
                ->whereKey($activity->id)
                ->exists()
        );

        $this->assertTrue(
            $opportunity->activities()
                ->whereKey($activity->id)
                ->exists()
        );

        $this->assertTrue(
            $user->assignedActivities()
                ->whereKey($activity->id)
                ->exists()
        );
    }
}
