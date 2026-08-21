<?php

namespace App\Services;

use App\Jobs\ExecuteAutomationActionJob;
use App\Support\AutomationAction;
use Illuminate\Support\Str;

class AutomationActionQueueService
{
    public function dispatch(
        AutomationAction $action,
        array $context = [],
        ?string $executionKey = null
    ): string {
        $executionKey = trim(
            $executionKey
            ?? ''
        );

        if ($executionKey === '') {
            $executionKey =
                (string) Str::uuid();
        }

        ExecuteAutomationActionJob::dispatch(
            tenantId: $action->tenantId,
            type: $action->type->value,
            executionKey: $executionKey,
            parameters: $action->parameters,
            context: $context,
        );

        return $executionKey;
    }
}