<?php

namespace App\Services;

use App\Models\Tenant;
use Carbon\CarbonImmutable;

class TrialExpirationTenantRunner
{
    public function __construct(
        private readonly TrialService $trialService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function dispatch(
        ?CarbonImmutable $moment = null,
    ): int {
        $moment ??= CarbonImmutable::now('UTC');

        $blocked = 0;

        Tenant::query()
            ->where('status', 'active')
            ->whereNotNull('trial_started_at')
            ->whereNotNull('trial_ends_at')
            ->where(
                'trial_ends_at',
                '<=',
                $moment
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($tenants) use (
                    $moment,
                    &$blocked
                ): void {
                    foreach ($tenants as $tenant) {
                        $this->tenantContext->set(
                            $tenant
                        );

                        try {
                            if (
                                $this->trialService
                                    ->blockIfExpired(
                                        $tenant,
                                        $moment
                                    )
                            ) {
                                $blocked++;
                            }
                        } finally {
                            $this->tenantContext->clear();
                        }
                    }
                }
            );

        return $blocked;
    }
}