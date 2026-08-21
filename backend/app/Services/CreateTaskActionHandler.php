<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\ActivityType;
use App\Enums\AutomationActionType;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use RuntimeException;

class CreateTaskActionHandler implements AutomationActionHandler
{
    public function __construct(
        private readonly ActivityService $activities
    ) {
    }

    public function handle(
        AutomationAction $action,
        array $context
    ): AutomationActionResult {
        if (
            $action->type
            !== AutomationActionType::CREATE_TASK
        ) {
            throw new RuntimeException(
                'Invalid action type for create task handler.'
            );
        }

        $data = [
            'type' =>
                ActivityType::TASK,
        ];

        foreach ([
            'title',
            'description',
            'due_at',
            'customer_id',
            'opportunity_id',
            'responsible_user_id',
        ] as $field) {
            if (
                array_key_exists(
                    $field,
                    $action->parameters
                )
            ) {
                $data[$field] =
                    $action->parameters[$field];
            }
        }

        $activity =
            $this->activities->create(
                $data
            );

        return AutomationActionResult::success([
            'activity_id' =>
                $activity->id,

            'type' =>
                $activity->type->value,

            'status' =>
                $activity->status->value,
        ]);
    }
}