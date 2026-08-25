<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAdminAuditLog extends Model
{
    protected $fillable = [
        'platform_admin_id',
        'tenant_id',
        'action',
        'entity_type',
        'entity_id',
        'before_state',
        'after_state',
        'ip_address',
        'result',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
        ];
    }

    public function platformAdmin(): BelongsTo
    {
        return $this->belongsTo(
            PlatformAdmin::class
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }
}
