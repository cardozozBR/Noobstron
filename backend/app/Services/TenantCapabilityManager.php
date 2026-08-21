<?php

namespace App\Services;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use App\Support\TenantCapabilities;
use Illuminate\Support\Facades\DB;

class TenantCapabilityManager
{
    public function __construct(
        private readonly TenantCapabilities $capabilities,
        private readonly AuditService $audits
    ) {
    }

    public function setFeature(
        Tenant $tenant,
        Feature $feature,
        bool $enabled,
        ?User $actor = null
    ): TenantFeature {
        $before = $this->capabilities->enabled(
            $tenant,
            $feature
        );

        $tenantFeature = $this->capabilities->set(
            $tenant,
            $feature,
            $enabled
        );

        if ($before !== $enabled) {
            $this->audits->log(
                'tenant.feature.updated',
                sprintf(
                    'Feature %s alterada de %s para %s.',
                    $feature->value,
                    $before ? 'enabled' : 'disabled',
                    $enabled ? 'enabled' : 'disabled'
                ),
                $actor,
                $tenant
            );
        }

        return $tenantFeature;
    }

    public function setLimit(
        Tenant $tenant,
        Feature $feature,
        ?int $limit,
        ?User $actor = null
    ): TenantFeature {
        $before = $this->capabilities->limit(
            $tenant,
            $feature
        );

        $tenantFeature = $this->capabilities->setLimit(
            $tenant,
            $feature,
            $limit
        );

        if ($before !== $limit) {
            $this->audits->log(
                'tenant.feature.limit.updated',
                sprintf(
                    'Limite da feature %s alterado de %s para %s.',
                    $feature->value,
                    $this->formatLimit($before),
                    $this->formatLimit($limit)
                ),
                $actor,
                $tenant
            );
        }

        return $tenantFeature;
    }

    public function applyProfile(
        Tenant $tenant,
        array $definitions,
        ?User $actor = null
    ): void {
        DB::transaction(function () use (
            $tenant,
            $definitions,
            $actor
        ): void {
            $before = $this->snapshot(
                $tenant,
                $definitions
            );

            $this->capabilities->applyProfile(
                $tenant,
                $definitions
            );

            $after = $this->snapshot(
                $tenant,
                $definitions
            );

            if ($before === $after) {
                return;
            }

            $this->audits->log(
                'tenant.capability_profile.applied',
                'Perfil de capacidades do tenant atualizado.',
                $actor,
                $tenant
            );
        });
    }

    private function snapshot(
        Tenant $tenant,
        array $definitions
    ): array {
        $snapshot = [];

        foreach ($definitions as $definition) {
            $feature = $definition['feature'] ?? null;

            if (!$feature instanceof Feature) {
                throw new \InvalidArgumentException(
                    'Capability profile feature must be a Feature enum.'
                );
            }

            $snapshot[$feature->value] = [
                'enabled' => $this->capabilities->enabled(
                    $tenant,
                    $feature
                ),
                'limit' => $this->capabilities->limit(
                    $tenant,
                    $feature
                ),
            ];
        }

        ksort($snapshot);

        return $snapshot;
    }

    private function formatLimit(
        ?int $limit
    ): string {
        return $limit === null
            ? 'unlimited'
            : (string) $limit;
    }
}