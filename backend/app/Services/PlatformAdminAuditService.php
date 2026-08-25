<?php

namespace App\Services;

use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use RuntimeException;

class PlatformAdminAuditService
{
    public const RESULT_SUCCESS = 'success';

    public const RESULT_FAILURE = 'failure';

    public function log(
        string $action,
        ?Tenant $tenant = null,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        string $result = self::RESULT_SUCCESS,
        ?string $reason = null,
        ?PlatformAdmin $adminOverride = null,
        ?Request $request = null,
    ): PlatformAdminAuditLog {
        $admin = $adminOverride
            ?? auth('platform')->user();

        if (
            $admin !== null
            && ! $admin instanceof PlatformAdmin
        ) {
            throw new RuntimeException(
                'Invalid platform administrator.'
            );
        }

        $request ??= request();

        return PlatformAdminAuditLog::query()->create([
            'platform_admin_id' => $admin?->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId !== null
                ? (string) $entityId
                : null,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'ip_address' => $request->ip(),
            'result' => $result,
            'reason' => $reason,
        ]);
    }
}
