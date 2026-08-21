<?php

namespace App\Services;

use App\Contracts\WhatsAppProvider;
use RuntimeException;

class WhatsAppProviderRegistry
{
    /**
     * @var array<string, WhatsAppProvider>
     */
    private array $providers = [];

    public function register(
        WhatsAppProvider $provider
    ): void {
        $name = strtolower(
            trim(
                $provider->name()
            )
        );

        if ($name === '') {
            throw new RuntimeException(
                'WhatsApp provider name is required.'
            );
        }

        $this->providers[
            $name
        ] = $provider;
    }

    public function get(
        string $name
    ): WhatsAppProvider {
        $name = strtolower(
            trim(
                $name
            )
        );

        if (
            ! array_key_exists(
                $name,
                $this->providers
            )
        ) {
            throw new RuntimeException(
                'WhatsApp provider is not registered: '
                . $name
            );
        }

        return $this->providers[
            $name
        ];
    }

    public function has(
        string $name
    ): bool {
        return array_key_exists(
            strtolower(
                trim(
                    $name
                )
            ),
            $this->providers
        );
    }
}