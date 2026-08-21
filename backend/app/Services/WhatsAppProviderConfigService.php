<?php

namespace App\Services;

use App\Models\WhatsAppProviderConfig;
use RuntimeException;

class WhatsAppProviderConfigService
{
    public function create(
        array $attributes
    ): WhatsAppProviderConfig {
        return WhatsAppProviderConfig::query()
            ->create(
                $attributes
            );
    }

    public function update(
        WhatsAppProviderConfig $config,
        array $attributes
    ): WhatsAppProviderConfig {
        $this->assertCurrentTenant(
            $config
        );

        $config->fill(
            $attributes
        );

        $config->save();

        return $config->refresh();
    }

    public function active(
        string $provider
    ): WhatsAppProviderConfig {
        $provider = strtolower(
            trim(
                $provider
            )
        );

        $config = WhatsAppProviderConfig::query()
            ->where(
                'provider',
                $provider
            )
            ->where(
                'active',
                true
            )
            ->first();

        if (! $config) {
            throw new RuntimeException(
                'Active WhatsApp provider configuration was not found.'
            );
        }

        return $config;
    }

    private function assertCurrentTenant(
        WhatsAppProviderConfig $config
    ): void {
        $tenant = app(
            TenantContext::class
        )->get();

        if (
            (int) $config->tenant_id !==
            (int) $tenant->id
        ) {
            throw new RuntimeException(
                'WhatsApp provider configuration does not belong to current tenant.'
            );
        }
    }
}