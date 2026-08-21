<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\OpportunityService;
use App\Services\PipelineService;
use App\Services\PipelineStageService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
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

        app(TenantContext::class)->set(
            $tenant
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
            'tenant_id' => $tenant->id,
            'name' => 'Activity User',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function customer(
        Tenant $tenant,
        string $name
    ): Customer {
        app(TenantContext::class)->set(
            $tenant
        );

        return Customer::create([
            'type' =>
                CustomerType::COMPANY,
            'name' => $name,
        ]);
    }

    private function opportunity(
        Tenant $tenant,
        Customer $customer
    ) {
        app(TenantContext::class)->set(
            $tenant
        );

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $stage = app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        return app(
            OpportunityService::class
        )->create([
            'name' => 'Oportunidade',
            'customer_id' =>
                $customer->id,
            'pipeline_id' =>
                $pipeline->id,
            'pipeline_stage_id' =>
                $stage->id,
            'value_minor' => 100000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);
    }

    public function test_activity_can_be_created(): void
    {
        $this->tenant(
            'activity-service-create'
        );

        $activity = app(
            ActivityService::class
        )->create([
            'type' =>
                ActivityType::TASK,
            'title' =>
                'Enviar proposta',
        ]);

        $this->assertSame(
            ActivityType::TASK,
            $activity->type
        );

        $this->assertSame(
            ActivityStatus::PENDING,
            $activity->status
        );
    }

    public function test_text_fields_are_normalized(): void
    {
        $this->tenant(
            'activity-service-text'
        );

        $activity = app(
            ActivityService::class
        )->create([
            'title' =>
                '  Follow-up  ',
            'description' =>
                '  Conversar amanhã  ',
        ]);

        $this->assertSame(
            'Follow-up',
            $activity->title
        );

        $this->assertSame(
            'Conversar amanhã',
            $activity->description
        );
    }

    public function test_activity_relations_can_be_defined(): void
    {
        $tenant = $this->tenant(
            'activity-service-relations'
        );

        $customer = $this->customer(
            $tenant,
            'Cliente'
        );

        $user = $this->user(
            $tenant,
            'activity-service@local'
        );

        $opportunity =
            $this->opportunity(
                $tenant,
                $customer
            );

        $activity = app(
            ActivityService::class
        )->create([
            'type' =>
                ActivityType::MEETING,
            'title' => 'Reunião',
            'customer_id' =>
                $customer->id,
            'opportunity_id' =>
                $opportunity->id,
            'responsible_user_id' =>
                $user->id,
        ]);

        $this->assertSame(
            $customer->id,
            $activity->customer_id
        );

        $this->assertSame(
            $opportunity->id,
            $activity->opportunity_id
        );

        $this->assertSame(
            $user->id,
            $activity->responsible_user_id
        );
    }

    public function test_activity_can_be_completed(): void
    {
        $this->tenant(
            'activity-service-complete'
        );

        $service = app(
            ActivityService::class
        );

        $activity = $service->create([
            'title' => 'Tarefa',
        ]);

        $activity = $service->complete(
            $activity
        );

        $this->assertSame(
            ActivityStatus::COMPLETED,
            $activity->status
        );

        $this->assertNotNull(
            $activity->completed_at
        );
    }

    public function test_completed_activity_can_be_reopened(): void
    {
        $this->tenant(
            'activity-service-reopen'
        );

        $service = app(
            ActivityService::class
        );

        $activity = $service->create([
            'title' => 'Tarefa',
            'status' =>
                ActivityStatus::COMPLETED,
        ]);

        $activity = $service->reopen(
            $activity
        );

        $this->assertSame(
            ActivityStatus::PENDING,
            $activity->status
        );

        $this->assertNull(
            $activity->completed_at
        );
    }

    public function test_activity_can_be_cancelled(): void
    {
        $this->tenant(
            'activity-service-cancel'
        );

        $service = app(
            ActivityService::class
        );

        $activity = $service->create([
            'title' => 'Tarefa',
        ]);

        $activity = $service->cancel(
            $activity
        );

        $this->assertSame(
            ActivityStatus::CANCELLED,
            $activity->status
        );
    }

    public function test_partial_update_preserves_existing_values(): void
    {
        $this->tenant(
            'activity-service-update'
        );

        $service = app(
            ActivityService::class
        );

        $activity = $service->create([
            'type' =>
                ActivityType::CALL,
            'title' => 'Ligação',
            'description' => 'Inicial',
        ]);

        $activity = $service->update(
            $activity,
            [
                'title' =>
                    'Ligação atualizada',
            ]
        );

        $this->assertSame(
            ActivityType::CALL,
            $activity->type
        );

        $this->assertSame(
            'Inicial',
            $activity->description
        );
    }

    public function test_responsible_can_be_removed(): void
    {
        $tenant = $this->tenant(
            'activity-service-remove-user'
        );

        $user = $this->user(
            $tenant,
            'remove-user@local'
        );

        $service = app(
            ActivityService::class
        );

        $activity = $service->create([
            'title' => 'Tarefa',
            'responsible_user_id' =>
                $user->id,
        ]);

        $activity = $service->update(
            $activity,
            [
                'responsible_user_id'
                    => null,
            ]
        );

        $this->assertNull(
            $activity->responsible_user_id
        );
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->tenant(
            'activity-service-type'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(ActivityService::class)
            ->create([
                'type' => 'invalid',
                'title' => 'Tarefa',
            ]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->tenant(
            'activity-service-status'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(ActivityService::class)
            ->create([
                'status' => 'invalid',
                'title' => 'Tarefa',
            ]);
    }

    public function test_customer_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'activity-service-customer-a'
        );

        $customer = $this->customer(
            $tenantA,
            'Cliente A'
        );

        $this->tenant(
            'activity-service-customer-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(ActivityService::class)
            ->create([
                'title' => 'Tarefa',
                'customer_id' =>
                    $customer->id,
            ]);
    }

    public function test_activity_from_other_tenant_cannot_be_updated(): void
    {
        $this->tenant(
            'activity-service-a'
        );

        $service = app(
            ActivityService::class
        );

        $activity = $service->create([
            'title' => 'Tenant A',
        ]);

        $this->tenant(
            'activity-service-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->update(
            $activity,
            [
                'title' => 'Inválido',
            ]
        );
    }
}
