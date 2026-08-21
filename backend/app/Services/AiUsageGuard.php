<?php

namespace App\Services;

use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\Tenant;
use InvalidArgumentException;

class AiUsageGuard
{
    public function __construct(
        private readonly TenantUsageGuard $usageGuard,
    ) {
    }

    public function assertCanRequest(
        Tenant $tenant,
        int $estimatedTokens
    ): void {
        if ($estimatedTokens < 0) {
            throw new InvalidArgumentException(
                'Estimated AI token usage cannot be negative.'
            );
        }

        if ($estimatedTokens === 0) {
            return;
        }

        try {
            $this->usageGuard->assertCanConsume(
                $tenant,
                UsageMetric::AI_TOKENS,
                $estimatedTokens
            );
        } catch (
            UsageBlockedException $exception
        ) {
            /*
             * Transitional compatibility:
             *
             * tenants created before usage plans existed may
             * not yet have an active subscription or AI quota.
             *
             * Once every production tenant is commercially
             * migrated, this compatibility branch can be
             * removed and unavailable quotas can fail closed.
             */
            if (
                $exception->reason ===
                'unavailable'
            ) {
                return;
            }

            throw $exception;
        }
    }
}