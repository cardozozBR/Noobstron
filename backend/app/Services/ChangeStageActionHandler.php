<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use RuntimeException;

class ChangeStageActionHandler implements AutomationActionHandler
{
    public function __construct(
        private readonly OpportunityService $opportunities
    ) {
    }

    public function handle(
        AutomationAction $action,
        array $context
    ): AutomationActionResult {
        if (
            $action->type
            !== AutomationActionType::CHANGE_STAGE
        ) {
            throw new RuntimeException(
                'Invalid action type for change stage handler.'
            );
        }

        $opportunityId = $this->requiredPositiveInt(
            $action->parameters,
            'opportunity_id'
        );

        $stageId = $this->requiredPositiveInt(
            $action->parameters,
            'pipeline_stage_id'
        );

        $opportunity = Opportunity::query()
            ->findOrFail($opportunityId);

        $stage = PipelineStage::query()
            ->findOrFail($stageId);

        $updated = $this->opportunities
            ->moveToStage(
                $opportunity,
                $stage
            );

        return AutomationActionResult::success([
            'opportunity_id' =>
                (int) $updated->id,

            'pipeline_id' =>
                (int) $updated->pipeline_id,

            'pipeline_stage_id' =>
                (int) $updated->pipeline_stage_id,
        ]);
    }

    private function requiredPositiveInt(
        array $parameters,
        string $key
    ): int {
        $value = $parameters[$key] ?? null;

        if (
            is_int($value)
            && $value > 0
        ) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        throw new RuntimeException(
            "Automation action parameter {$key} is required."
        );
    }
}