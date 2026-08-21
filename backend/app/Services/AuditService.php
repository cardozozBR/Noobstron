<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Services\TenantContext;

class AuditService
{
    public function log(
    string $action,
    ?string $description = null,
    ?\App\Models\User $userOverride = null,
    ?\App\Models\Tenant $tenantOverride = null,
): AuditLog {
    $user = $userOverride ?? auth()->user();
    $tenant = $tenantOverride ?? app(TenantContext::class)->get();

        return AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
        ]);
    }
}