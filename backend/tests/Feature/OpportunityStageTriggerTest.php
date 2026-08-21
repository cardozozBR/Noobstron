<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\TriggerType;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Services\OpportunityService;
use App\Services\TenantContext;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityStageTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_change_dispatches_trigger(): void
    {
        $tenant = $this->tenant('opportunity-trigger');

        $customer = Customer::query()->create([
            'type' => 'individual',
            'name' => 'Customer',
        ]);

        $pipeline = Pipeline::query()->create([
            'name' => 'Sales',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stageA = PipelineStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Stage A',
            'position' => 1,
            'is_active' => true,
        ]);

        $stageB = PipelineStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Stage B',
            'position' => 2,
            'is_active' => true,
        ]);

        $opportunity = Opportunity::query()->create([
            'name' => 'Opportunity',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'value_minor' => 1000,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $listener = new class implements TriggerListener
        {
            public array $occurrences = [];

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->occurrences[] = $occurrence;
            }
        };

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::OPPORTUNITY_STAGE_CHANGED->value,
            $listener
        );

        $this->app->instance(
            TriggerDispatcher::class,
            $dispatcher
        );

        $service = $this->app->make(
            OpportunityService::class
        );

        $updated = $service->update(
            $opportunity,
            [
                'pipeline_stage_id' => $stageB->id,
            ]
        );

        $this->assertSame(
            $stageB->id,
            $updated->pipeline_stage_id
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $occurrence = $listener->occurrences[0];

        $this->assertSame(
            TriggerType::OPPORTUNITY_STAGE_CHANGED,
            $occurrence->type
        );

        $this->assertSame(
            $tenant->id,
            $occurrence->tenantId
        );

        $this->assertSame(
            'opportunity',
            $occurrence->subjectType
        );

        $this->assertSame(
            $opportunity->id,
            $occurrence->subjectId
        );

        $this->assertSame(
            $stageA->id,
            $occurrence->payload['previous_stage_id']
        );

        $this->assertSame(
            $stageB->id,
            $occurrence->payload['new_stage_id']
        );
    }

    public function test_update_without_stage_change_does_not_dispatch(): void
    {
        $this->tenant('opportunity-no-trigger');

        [$customer, $pipeline, $stage] =
            $this->fixture();

        $opportunity = Opportunity::query()->create([
            'name' => 'Opportunity',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 1000,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $listener = new class implements TriggerListener
        {
            public int $calls = 0;

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->calls++;
            }
        };

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::OPPORTUNITY_STAGE_CHANGED->value,
            $listener
        );

        $this->app->instance(
            TriggerDispatcher::class,
            $dispatcher
        );

        $this->app->make(
            OpportunityService::class
        )->update(
            $opportunity,
            [
                'name' => 'Opportunity Updated',
            ]
        );

        $this->assertSame(
            0,
            $listener->calls
        );
    }

    public function test_same_stage_does_not_dispatch(): void
    {
        $this->tenant('opportunity-same-stage');

        [$customer, $pipeline, $stage] =
            $this->fixture();

        $opportunity = Opportunity::query()->create([
            'name' => 'Opportunity',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 1000,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $listener = new class implements TriggerListener
        {
            public int $calls = 0;

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->calls++;
            }
        };

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::OPPORTUNITY_STAGE_CHANGED->value,
            $listener
        );

        $this->app->instance(
            TriggerDispatcher::class,
            $dispatcher
        );

        $this->app->make(
            OpportunityService::class
        )->update(
            $opportunity,
            [
                'pipeline_stage_id' => $stage->id,
            ]
        );

        $this->assertSame(
            0,
            $listener->calls
        );
    }

    private function fixture(): array
    {
        $customer = Customer::query()->create([
            'type' => 'individual',
            'name' => 'Customer',
        ]);

        $pipeline = Pipeline::query()->create([
            'name' => 'Sales',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Stage',
            'position' => 1,
            'is_active' => true,
        ]);

        return [
            $customer,
            $pipeline,
            $stage,
        ];
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Sao_Paulo',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}