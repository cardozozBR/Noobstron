<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;

class AuditService
{
    public function log(
        string $action,
        ?string $description = null,
        ?User $userOverride = null,
        ?Tenant $tenantOverride = null,
    ): AuditLog {
        $authenticatedUser = auth()->user();

        $user = $userOverride;

        if (
            $user === null
            && $authenticatedUser instanceof User
        ) {
            $user = $authenticatedUser;
        }

        $tenant = $tenantOverride
            ?? app(TenantContext::class)->get();

        return AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
