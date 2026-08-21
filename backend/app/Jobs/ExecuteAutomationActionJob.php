<?php

namespace App\Jobs;

use App\Enums\AutomationActionType;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\AutomationActionExecutionLedger;
use App\Services\AutomationActionExecutor;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ExecuteAutomationActionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function backoff(): array
    {
        return [
            60,
            300,
        ];
    }

    public function __construct(
        public readonly int $tenantId,
        public readonly string $type,
        public readonly string $executionKey,
        public readonly array $parameters = [],
        public readonly array $context = [],
    ) {
    }

    public function handle(
        TenantContext $tenantContext,
        AutomationActionExecutor $executor,
        AutomationActionExecutionLedger $ledger,
        AuditService $audits
    ): void {
        $tenant = Tenant::withoutGlobalScopes()
            ->findOrFail(
                $this->tenantId
            );

        $tenantContext->set(
            $tenant
        );

        try {
            $execution = $ledger->claim(
                $this->tenantId,
                $this->executionKey,
                $this->type
            );

            if (
                $ledger->isCompleted(
                    $execution
                )
            ) {
                return;
            }

            Log::info(
                'automation.action.started',
                $this->logContext()
            );

            $action = AutomationAction::make(
                $this->tenantId,
                AutomationActionType::from(
                    $this->type
                ),
                $this->parameters
            );

            $context = $this->context;

            $context['tenant_id'] =
                $this->tenantId;

            $result = $executor->execute(
                $action,
                $context
            );

            if (! $result->successful) {
                throw new RuntimeException(
                    $result->error
                    ?? 'Automation action execution failed.'
                );
            }

            $ledger->complete(
                $execution
            );

            Log::info(
                'automation.action.completed',
                $this->logContext()
            );

            $audits->log(
                'automation.action.completed',
                $this->auditDescription(),
                null,
                $tenant
            );
        } finally {
            $tenantContext->clear();
        }
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $tenant = Tenant::withoutGlobalScopes()
            ->find(
                $this->tenantId
            );

        if ($tenant !== null) {
            app(
                TenantContext::class
            )->set(
                $tenant
            );
        }

        Log::error(
            'automation.action.failed',
            array_merge(
                $this->logContext(),
                [
                    'exception_class' =>
                        $exception !== null
                            ? $exception::class
                            : null,

                    'exception_message' =>
                        $exception?->getMessage(),
                ]
            )
        );

        if ($tenant !== null) {
            app(
                AuditService::class
            )->log(
                'automation.action.failed',
                $this->auditDescription(
                    $exception?->getMessage()
                ),
                null,
                $tenant
            );
        }

        app(
            TenantContext::class
        )->clear();
    }

    private function auditDescription(
        ?string $error = null
    ): string {
        $description =
            'execution_key='
            . $this->executionKey
            . '; action_type='
            . $this->type;

        if ($error !== null && trim($error) !== '') {
            $description .=
                '; error='
                . trim($error);
        }

        return $description;
    }

    private function logContext(): array
    {
        return [
            'tenant_id' =>
                $this->tenantId,

            'execution_key' =>
                $this->executionKey,

            'action_type' =>
                $this->type,
        ];
    }
}