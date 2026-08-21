<?php

namespace App\Services;

use App\Contracts\AiProvider;
use RuntimeException;

class AiProviderRegistry
{
    /**
     * @var array<string, AiProvider>
     */
    private array $providers = [];

    public function register(
        AiProvider $provider
    ): void {
        $code = $this->normalize(
            $provider->code()
        );

        if (isset(
            $this->providers[$code]
        )) {
            throw new RuntimeException(
                "AI provider [{$code}] is already registered."
            );
        }

        $this->providers[$code] =
            $provider;
    }

    public function resolve(
        string $code
    ): AiProvider {
        $normalized = $this->normalize(
            $code
        );

        if (! isset(
            $this->providers[$normalized]
        )) {
            throw new RuntimeException(
                "AI provider [{$normalized}] is not registered."
            );
        }

        return $this->providers[
            $normalized
        ];
    }

    public function has(
        string $code
    ): bool {
        $normalized = $this->normalize(
            $code
        );

        return isset(
            $this->providers[
                $normalized
            ]
        );
    }

    /**
     * @return array<string>
     */
    public function codes(): array
    {
        $codes = array_keys(
            $this->providers
        );

        sort(
            $codes
        );

        return $codes;
    }

    private function normalize(
        string $code
    ): string {
        $normalized = strtolower(
            trim(
                $code
            )
        );

        if ($normalized === '') {
            throw new RuntimeException(
                'AI provider code is required.'
            );
        }

        return $normalized;
    }
}