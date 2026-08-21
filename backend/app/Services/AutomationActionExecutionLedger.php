<?php

namespace App\Services;

use App\Models\AutomationActionExecution;
use Illuminate\Database\QueryException;

class AutomationActionExecutionLedger
{
    public function claim(
        int $tenantId,
        string $executionKey,
        string $actionType
    ): AutomationActionExecution {
        $executionKey = trim($executionKey);
        $actionType = trim($actionType);

        if ($executionKey === '') {
            throw new \InvalidArgumentException(
                'Automation execution key is required.'
            );
        }

        if ($actionType === '') {
            throw new \InvalidArgumentException(
                'Automation action type is required.'
            );
        }

        try {
            return AutomationActionExecution::query()
                ->create([
                    'tenant_id' =>
                        $tenantId,

                    'execution_key' =>
                        $executionKey,

                    'action_type' =>
                        $actionType,
                ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            return AutomationActionExecution::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'execution_key',
                    $executionKey
                )
                ->firstOrFail();
        }
    }

    public function isCompleted(
        AutomationActionExecution $execution
    ): bool {
        return $execution->completed_at !== null;
    }

    public function complete(
        AutomationActionExecution $execution
    ): AutomationActionExecution {
        if ($execution->completed_at !== null) {
            return $execution;
        }

        $execution->forceFill([
            'completed_at' => now(),
        ])->save();

        return $execution->refresh();
    }

    private function isUniqueViolation(
        QueryException $exception
    ): bool {
        $sqlState =
            $exception->errorInfo[0]
            ?? null;

        return in_array(
            $sqlState,
            [
                '23000',
                '23505',
            ],
            true
        );
    }
}