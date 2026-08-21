<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use RuntimeException;

class AutomationActionExecutor
{
    /**
     * @var array<string, AutomationActionHandler>
     */
    private array $handlers = [];

    public function register(
        AutomationActionType $type,
        AutomationActionHandler $handler
    ): void {
        $this->handlers[
            $type->value
        ] = $handler;
    }

    public function execute(
        AutomationAction $action,
        array $context = []
    ): AutomationActionResult {
        $this->assertTenant(
            $action,
            $context
        );

        $handler =
            $this->handlers[
                $action->type->value
            ] ?? null;

        if ($handler === null) {
            throw new RuntimeException(
                'No automation action handler registered for: '
                . $action->type->value
            );
        }

        return $handler->handle(
            $action,
            $context
        );
    }

    public function has(
        AutomationActionType $type
    ): bool {
        return isset(
            $this->handlers[
                $type->value
            ]
        );
    }

    private function assertTenant(
        AutomationAction $action,
        array $context
    ): void {
        if (
            ! array_key_exists(
                'tenant_id',
                $context
            )
        ) {
            return;
        }

        $contextTenantId =
            $context['tenant_id'];

        if (
            ! is_int($contextTenantId)
            && ! (
                is_string($contextTenantId)
                && ctype_digit(
                    $contextTenantId
                )
            )
        ) {
            throw new RuntimeException(
                'Invalid automation context tenant.'
            );
        }

        if (
            (int) $contextTenantId
            !== $action->tenantId
        ) {
            throw new RuntimeException(
                'Automation action tenant mismatch.'
            );
        }
    }
}