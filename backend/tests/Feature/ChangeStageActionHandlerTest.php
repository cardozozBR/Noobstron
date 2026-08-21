<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Models\Tenant;
use App\Services\ChangeStageActionHandler;
use App\Services\OpportunityService;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use RuntimeException;

class ChangeStageActionHandlerTest extends TestCase
{
    use RefreshDatabase;
    public function test_opportunity_id_is_required(): void
    {
        $handler = new ChangeStageActionHandler(
            $this->createMock(
                OpportunityService::class
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $handler->handle(
            AutomationAction::make(
                1,
                AutomationActionType::CHANGE_STAGE,
                [
                    'pipeline_stage_id' => 2,
                ]
            ),
            [
                'tenant_id' => 1,
            ]
        );
    }

    public function test_stage_id_is_required(): void
    {
        $handler = new ChangeStageActionHandler(
            $this->createMock(
                OpportunityService::class
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $handler->handle(
            AutomationAction::make(
                1,
                AutomationActionType::CHANGE_STAGE,
                [
                    'opportunity_id' => 10,
                ]
            ),
            [
                'tenant_id' => 1,
            ]
        );
    }

    public function test_zero_opportunity_id_is_rejected(): void
    {
        $handler = new ChangeStageActionHandler(
            $this->createMock(
                OpportunityService::class
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $handler->handle(
            AutomationAction::make(
                1,
                AutomationActionType::CHANGE_STAGE,
                [
                    'opportunity_id' => 0,
                    'pipeline_stage_id' => 2,
                ]
            ),
            [
                'tenant_id' => 1,
            ]
        );
    }

    public function test_zero_stage_id_is_rejected(): void
    {
        $handler = new ChangeStageActionHandler(
            $this->createMock(
                OpportunityService::class
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $handler->handle(
            AutomationAction::make(
                1,
                AutomationActionType::CHANGE_STAGE,
                [
                    'opportunity_id' => 10,
                    'pipeline_stage_id' => 0,
                ]
            ),
            [
                'tenant_id' => 1,
            ]
        );
    }

    public function test_wrong_action_type_is_rejected(): void
    {
        $handler = new ChangeStageActionHandler(
            $this->createMock(
                OpportunityService::class
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $handler->handle(
            AutomationAction::make(
                1,
                AutomationActionType::CREATE_TASK,
                [
                    'opportunity_id' => 10,
                    'pipeline_stage_id' => 20,
                ]
            ),
            [
                'tenant_id' => 1,
            ]
        );
    }

    public function test_numeric_string_ids_are_accepted_until_domain_resolution(): void
    {
        $tenant = Tenant::query()
            ->create([
                'name' => 'Change Stage Tenant',
                'slug' => 'change-stage-action',
                'status' => 'active',
                'country_code' => 'BR',
                'locale' => 'pt-BR',
                'timezone' => 'America/Fortaleza',
                'currency' => 'BRL',
            ]);

        app(TenantContext::class)
            ->set($tenant);

        $handler = new ChangeStageActionHandler(
            $this->createMock(
                OpportunityService::class
            )
        );

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        $handler->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CHANGE_STAGE,
                [
                    'opportunity_id' => '999999',
                    'pipeline_stage_id' => '999998',
                ]
            ),
            [
                'tenant_id' => $tenant->id,
            ]
        );
    }
}