<?php

namespace App\Services;

use App\Enums\UsageMetric;
use App\Models\Tenant;
use App\Models\AiUsageRecord;
use App\Models\EmailMessage;
use App\Models\WhatsAppMessage;
use App\Models\User;
use RuntimeException;

class TenantUsageService
{
    public function __construct(
        private readonly TenantStorageUsage $storage,
    ) {
    }

    public function value(
        Tenant $tenant,
        UsageMetric $metric,
    ): int {
        return match ($metric) {
            UsageMetric::USERS =>
                $this->users($tenant),

            UsageMetric::STORAGE_BYTES =>
                $this->storage->bytes($tenant),

            UsageMetric::MESSAGES =>
                $this->messages($tenant),

            UsageMetric::AI_TOKENS =>
                $this->aiTokens($tenant),
        };
    }

    private function users(Tenant $tenant): int
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();
    }

    private function messages(Tenant $tenant): int
    {
        $email = EmailMessage::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        $whatsApp = WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('direction', 'outbound')
            ->count();

        return $email + $whatsApp;
    }

    private function aiTokens(Tenant $tenant): int
    {
        return (int) AiUsageRecord::query()
            ->where('tenant_id', $tenant->id)
            ->sum('total_tokens');
    }
}