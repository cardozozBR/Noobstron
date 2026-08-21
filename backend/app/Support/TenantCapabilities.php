<?php

namespace App\Support;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Support\Facades\DB;

class TenantCapabilities
{
    public function enabled(
        Tenant $tenant,
        Feature $feature
    ): bool {
        return TenantFeature::query()
            ->where('tenant_id', $tenant->id)
            ->where('feature', $feature->value)
            ->value('enabled') === true;
    }

    public function set(
        Tenant $tenant,
        Feature $feature,
        bool $enabled
    ): TenantFeature {
        return TenantFeature::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'feature' => $feature,
            ],
            [
                'enabled' => $enabled,
            ]
        );
    }

    public function limit(
        Tenant $tenant,
        Feature $feature
    ): ?int {
        $value = TenantFeature::query()
            ->where('tenant_id', $tenant->id)
            ->where('feature', $feature->value)
            ->value('limit_value');

        return $value === null
            ? null
            : (int) $value;
    }

    public function setLimit(
        Tenant $tenant,
        Feature $feature,
        ?int $limit
    ): TenantFeature {
        $this->assertValidLimit($limit);

        return TenantFeature::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'feature' => $feature,
            ],
            [
                'limit_value' => $limit,
            ]
        );
    }

    public function configure(
        Tenant $tenant,
        Feature $feature,
        bool $enabled,
        ?int $limit = null
    ): TenantFeature {
        $this->assertValidLimit($limit);

        return TenantFeature::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'feature' => $feature,
            ],
            [
                'enabled' => $enabled,
                'limit_value' => $limit,
            ]
        );
    }

    /**
     * @param array<int, array{
     *     feature: Feature,
     *     enabled: bool,
     *     limit?: int|null
     * }> $definitions
     */
    public function applyProfile(
        Tenant $tenant,
        array $definitions
    ): void {
        DB::transaction(function () use (
            $tenant,
            $definitions
        ): void {
            foreach ($definitions as $definition) {
                $feature = $definition['feature'] ?? null;
                $enabled = $definition['enabled'] ?? null;
                $limit = $definition['limit'] ?? null;

                if (!$feature instanceof Feature) {
                    throw new \InvalidArgumentException(
                        'Capability profile feature must be a Feature enum.'
                    );
                }

                if (!is_bool($enabled)) {
                    throw new \InvalidArgumentException(
                        'Capability profile enabled value must be boolean.'
                    );
                }

                $this->configure(
                    $tenant,
                    $feature,
                    $enabled,
                    $limit
                );
            }
        });
    }

    private function assertValidLimit(
        ?int $limit
    ): void {
        if ($limit !== null && $limit < 0) {
            throw new \InvalidArgumentException(
                'Feature limit cannot be negative.'
            );
        }
    }
}