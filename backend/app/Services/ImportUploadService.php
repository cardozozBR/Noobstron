<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\ImportTarget;
use App\Enums\UsageMetric;
use App\Models\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportUploadService
{
    public function __construct(
        private readonly CsvImportParser $parser
    ) {
    }

    public function store(
        UploadedFile $file,
        string $delimiter = ',',
        ?ImportTarget $target = null
    ): Import {
        $tenant = app(
            TenantContext::class
        )->get();

        try {
            app(
                TenantUsageGuard::class
            )->assertCanConsume(
                $tenant,
                UsageMetric::STORAGE_BYTES,
                (int) $file->getSize()
            );
        } catch (
            \App\Exceptions\UsageBlockedException $exception
        ) {
            if ($exception->reason !== 'unavailable') {
                throw $exception;
            }
        }

        $path = $file->store(
            'tenant-imports/'
                . $tenant->id,
            'local'
        );

        if ($path === false) {
            throw new RuntimeException(
                'Failed to store import file.'
            );
        }

        $import = Import::create([
            'tenant_id' => $tenant->id,
            'user_id' => Auth::id(),
            'target' => $target,
            'original_name' =>
                $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' =>
                $file->getClientMimeType(),
            'size' => $file->getSize(),
            'status' => ImportStatus::UPLOADED,
            'delimiter' => $delimiter,
            'encoding' => 'UTF-8',
        ]);

        try {
            $absolutePath = Storage::disk(
                'local'
            )->path(
                $path
            );

            $inspection = $this->parser->inspect(
                $absolutePath,
                $delimiter
            );

            $import->update([
                'status' => ImportStatus::PARSED,
                'header' => $inspection['header'],
                'row_count' =>
                    $inspection['row_count'],
                'delimiter' =>
                    $inspection['delimiter'],
                'encoding' =>
                    $inspection['encoding'],
            ]);
        } catch (\Throwable $exception) {
            $import->update([
                'status' => ImportStatus::FAILED,
                'error_message' =>
                    $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $import->fresh();
    }
}
