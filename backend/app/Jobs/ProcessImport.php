<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\ImportExecutionService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $importId,
        public readonly int $tenantId
    ) {
    }

    public function handle(
        ImportExecutionService $execution,
        TenantContext $tenantContext
    ): void {
        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->findOrFail(
                $this->tenantId
            );

        $tenantContext->set(
            $tenant
        );

        $import = Import::query()
            ->findOrFail(
                $this->importId
            );

        $execution->execute(
            $import
        );
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->find(
                $this->tenantId
            );

        if ($tenant === null) {
            return;
        }

        app(TenantContext::class)->set(
            $tenant
        );

        $import = Import::query()
            ->find(
                $this->importId
            );

        if ($import === null) {
            return;
        }

        if (
            $import->status?->isFinished()
            && $import->status !== ImportStatus::FAILED
        ) {
            return;
        }

        $import->update([
            'status' => ImportStatus::FAILED,
            'error_message' =>
                $exception?->getMessage()
                ?? 'Import job failed.',
            'completed_at' => now(),
        ]);

        app(AuditService::class)->log(
            'import.failed',
            'Importação falhou: '
                . $import->original_name
                . '.'
        );
    }
}
