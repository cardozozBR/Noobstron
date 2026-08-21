<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Models\Opportunity;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use RuntimeException;

class AssignResponsibleActionHandler implements AutomationActionHandler
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
            !== AutomationActionType::ASSIGN_RESPONSIBLE
        ) {
            throw new RuntimeException(
                'Invalid action type for assign responsible handler.'
            );
        }

        $opportunityId = $this->requiredPositiveInt(
            $action->parameters,
            'opportunity_id'
        );

        $responsibleUserId = $this->requiredPositiveInt(
            $action->parameters,
            'responsible_user_id'
        );

        $opportunity = Opportunity::query()
            ->findOrFail(
                $opportunityId
            );

        $updated = $this->opportunities
            ->update(
                $opportunity,
                [
                    'responsible_user_id' =>
                        $responsibleUserId,
                ]
            );

        return AutomationActionResult::success([
            'opportunity_id' =>
                (int) $updated->id,

            'responsible_user_id' =>
                (int) $updated->responsible_user_id,
        ]);
    }

    private function requiredPositiveInt(
        array $parameters,
        string $key
    ): int {
        $value =
            $parameters[$key] ?? null;

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