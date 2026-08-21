<?php

namespace App\Services;

use App\Jobs\ProcessImport;
use App\Models\Import;
use RuntimeException;

class ImportDispatchService
{
    public function dispatch(
        Import $import
    ): void {
        if ($import->target === null) {
            throw new RuntimeException(
                'Import target is required before dispatch.'
            );
        }

        ProcessImport::dispatch(
            $import->id,
            $import->tenant_id
        );
    }
}
