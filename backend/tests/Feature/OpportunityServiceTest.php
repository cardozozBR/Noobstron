<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OpportunityService;
use App\Services\PipelineService;
use App\Services\PipelineStageService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class OpportunityServiceTest extends TestCase
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

    private function customer(): Customer
    {
        return Customer::create([
            'type' => 'individual',
            'name' => 'Cliente',
        ]);
    }

    private function pipeline(
        string $name = 'Comercial',
        bool $default = true
    ): array {
        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => $name,
            'is_default' => $default,
        ]);

        $stageA = app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        $stageB = app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'Proposta',
            ]
        );

        return [
            $pipeline,
            $stageA,
            $stageB,
        ];
    }

    public function test_opportunity_uses_default_pipeline_when_not_informed(): void
    {
        $this->tenant(
            'opportunity-service-default'
        );

        $customer = $this->customer();

        [$pipeline, $firstStage] =
            $this->pipeline();

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'value_minor' => 10000,
            'currency' => 'BRL',
            'probability' => 20,
        ]);

        $this->assertSame(
            $pipeline->id,
            $opportunity->pipeline_id
        );

        $this->assertSame(
            $firstStage->id,
            $opportunity->pipeline_stage_id
        );
    }

    public function test_specific_pipeline_can_be_selected(): void
    {
        $this->tenant(
            'opportunity-service-pipeline'
        );

        $customer = $this->customer();

        $this->pipeline('Padrao', true);

        [$pipeline, $stage] =
            $this->pipeline(
                'Enterprise',
                false
            );

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => 'Enterprise Deal',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 20000,
            'currency' => 'BRL',
            'probability' => 40,
        ]);

        $this->assertSame(
            $pipeline->id,
            $opportunity->pipeline_id
        );

        $this->assertSame(
            $stage->id,
            $opportunity->pipeline_stage_id
        );
    }

    public function test_first_active_stage_is_used_when_stage_is_not_informed(): void
    {
        $this->tenant(
            'opportunity-service-first-stage'
        );

        $customer = $this->customer();

        [$pipeline, $firstStage] =
            $this->pipeline();

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);

        $this->assertSame(
            $firstStage->id,
            $opportunity->pipeline_stage_id
        );
    }

    public function test_inactive_stage_is_not_accepted(): void
    {
        $this->tenant(
            'opportunity-service-inactive-stage'
        );

        $customer = $this->customer();

        [$pipeline, $stage] =
            $this->pipeline();

        app(
            PipelineStageService::class
        )->update(
            $stage,
            [
                'is_active' => false,
            ]
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_inactive_pipeline_is_not_accepted(): void
    {
        $this->tenant(
            'opportunity-service-inactive-pipeline'
        );

        $customer = $this->customer();

        [$pipeline] =
            $this->pipeline();

        app(
            PipelineService::class
        )->update(
            $pipeline,
            [
                'name' => $pipeline->name,
                'is_active' => false,
            ]
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_opportunity_can_move_between_stages(): void
    {
        $this->tenant(
            'opportunity-service-move'
        );

        $customer = $this->customer();

        [$pipeline, $stageA, $stageB] =
            $this->pipeline();

        $service = app(
            OpportunityService::class
        );

        $opportunity = $service->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'value_minor' => 100,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $updated = $service->moveToStage(
            $opportunity,
            $stageB
        );

        $this->assertSame(
            $stageB->id,
            $updated->pipeline_stage_id
        );

        $this->assertSame(
            $pipeline->id,
            $updated->pipeline_id
        );
    }

    public function test_moving_to_stage_from_another_pipeline_changes_pipeline_too(): void
    {
        $this->tenant(
            'opportunity-service-move-pipeline'
        );

        $customer = $this->customer();

        [$pipelineA, $stageA] =
            $this->pipeline(
                'Pipeline A',
                true
            );

        [$pipelineB, $stageB] =
            $this->pipeline(
                'Pipeline B',
                false
            );

        $service = app(
            OpportunityService::class
        );

        $opportunity = $service->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipelineA->id,
            'pipeline_stage_id' => $stageA->id,
            'value_minor' => 100,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $updated = $service->moveToStage(
            $opportunity,
            $stageB
        );

        $this->assertSame(
            $pipelineB->id,
            $updated->pipeline_id
        );

        $this->assertSame(
            $stageB->id,
            $updated->pipeline_stage_id
        );
    }

    public function test_partial_update_preserves_existing_relations(): void
    {
        $this->tenant(
            'opportunity-service-partial'
        );

        $customer = $this->customer();

        [$pipeline, $stage] =
            $this->pipeline();

        $service = app(
            OpportunityService::class
        );

        $opportunity = $service->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 100,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $updated = $service->update(
            $opportunity,
            [
                'name' => 'Venda Atualizada',
                'probability' => 75,
            ]
        );

        $this->assertSame(
            'Venda Atualizada',
            $updated->name
        );

        $this->assertSame(
            75,
            $updated->probability
        );

        $this->assertSame(
            $pipeline->id,
            $updated->pipeline_id
        );

        $this->assertSame(
            $stage->id,
            $updated->pipeline_stage_id
        );
    }

    public function test_responsible_can_be_removed(): void
    {
        $this->tenant(
            'opportunity-service-responsible'
        );

        $responsible = User::create([
            'name' => 'Responsavel',
            'email' => 'responsavel@local',
            'password' => Hash::make(
                'password123'
            ),
            'role' => 'user',
        ]);

        $customer = $this->customer();

        [$pipeline, $stage] =
            $this->pipeline();

        $service = app(
            OpportunityService::class
        );

        $opportunity = $service->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'responsible_user_id' =>
                $responsible->id,
            'value_minor' => 100,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $updated = $service->update(
            $opportunity,
            [
                'responsible_user_id' => null,
            ]
        );

        $this->assertNull(
            $updated->responsible_user_id
        );
    }

    public function test_text_fields_are_normalized(): void
    {
        $this->tenant(
            'opportunity-service-normalize'
        );

        $customer = $this->customer();

        $this->pipeline();

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => '  Venda  ',
            'customer_id' => $customer->id,
            'value_minor' => 100,
            'currency' => ' brl ',
            'probability' => 10,
            'notes' => '  Observacao  ',
        ]);

        $this->assertSame(
            'Venda',
            $opportunity->name
        );

        $this->assertSame(
            'BRL',
            $opportunity->currency
        );

        $this->assertSame(
            'Observacao',
            $opportunity->notes
        );
    }

    public function test_probability_above_one_hundred_is_rejected(): void
    {
        $this->tenant(
            'opportunity-service-probability'
        );

        $customer = $this->customer();

        $this->pipeline();

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 101,
        ]);
    }

    public function test_negative_value_is_rejected(): void
    {
        $this->tenant(
            'opportunity-service-negative'
        );

        $customer = $this->customer();

        $this->pipeline();

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'value_minor' => -1,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_customer_from_other_tenant_is_rejected(): void
    {
        $this->tenant(
            'opportunity-service-customer-a'
        );

        $customer = $this->customer();

        $this->tenant(
            'opportunity-service-customer-b'
        );

        $this->pipeline();

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_responsible_from_other_tenant_is_rejected(): void
    {
        $this->tenant(
            'opportunity-service-user-a'
        );

        $user = User::create([
            'name' => 'User A',
            'email' => 'user-a@local',
            'password' => Hash::make(
                'password123'
            ),
            'role' => 'user',
        ]);

        $this->tenant(
            'opportunity-service-user-b'
        );

        $customer = $this->customer();

        $this->pipeline();

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpportunityService::class
        )->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'responsible_user_id' => $user->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_opportunity_from_other_tenant_cannot_be_updated(): void
    {
        $this->tenant(
            'opportunity-service-update-a'
        );

        $customer = $this->customer();

        $this->pipeline();

        $service = app(
            OpportunityService::class
        );

        $opportunity = $service->create([
            'name' => 'Venda',
            'customer_id' => $customer->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);

        $this->tenant(
            'opportunity-service-update-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        $service->update(
            $opportunity,
            [
                'name' => 'Invalida',
            ]
        );
    }
}
