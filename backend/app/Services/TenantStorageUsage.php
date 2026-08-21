<?php

namespace App\Services;

use App\Models\Import;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class TenantStorageUsage
{
    public function bytes(Tenant $tenant): int
    {
        $imports = (int) Import::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->sum('size');

        $branding = 0;

        if (
            $tenant->logo_path !== null
            && trim($tenant->logo_path) !== ''
            && Storage::disk('public')
                ->exists($tenant->logo_path)
        ) {
            $branding = (int) Storage::disk('public')
                ->size($tenant->logo_path);
        }

        return $imports + $branding;
    }
}