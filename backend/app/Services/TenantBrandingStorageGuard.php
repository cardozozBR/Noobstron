<?php

namespace App\Services;

use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TenantBrandingStorageGuard
{
    public function __construct(
        private readonly TenantUsageGuard $usageGuard,
    ) {
    }

    public function assertCanStoreLogo(
        Tenant $tenant,
        UploadedFile $logo
    ): void {
        $newSize = (int) $logo->getSize();

        $oldSize = $this->currentLogoSize(
            $tenant
        );

        $additionalBytes = max(
            0,
            $newSize - $oldSize
        );

        if ($additionalBytes === 0) {
            return;
        }

        try {
            $this->usageGuard->assertCanConsume(
                $tenant,
                UsageMetric::STORAGE_BYTES,
                $additionalBytes
            );
        } catch (
            UsageBlockedException $exception
        ) {
            if (
                $exception->reason !==
                'unavailable'
            ) {
                throw $exception;
            }
        }
    }

    private function currentLogoSize(
        Tenant $tenant
    ): int {
        if (! $tenant->logo_path) {
            return 0;
        }

        $disk = Storage::disk(
            'public'
        );

        if (! $disk->exists(
            $tenant->logo_path
        )) {
            return 0;
        }

        return (int) $disk->size(
            $tenant->logo_path
        );
    }
}