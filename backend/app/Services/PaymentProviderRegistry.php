<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use RuntimeException;

class PaymentProviderRegistry
{
    /**
     * @var array<string, PaymentProvider>
     */
    private array $providers = [];

    public function register(
        string $code,
        PaymentProvider $provider,
    ): void {
        $code = $this->normalizeCode($code);

        if (isset($this->providers[$code])) {
            throw new RuntimeException(
                'Payment provider already registered.'
            );
        }

        $this->providers[$code] = $provider;
    }

    public function resolve(
        string $code
    ): PaymentProvider {
        $code = $this->normalizeCode($code);

        if (! isset($this->providers[$code])) {
            throw new RuntimeException(
                'Payment provider is not registered.'
            );
        }

        return $this->providers[$code];
    }

    public function has(
        string $code
    ): bool {
        return isset(
            $this->providers[
                $this->normalizeCode($code)
            ]
        );
    }

    private function normalizeCode(
        string $code
    ): string {
        $code = strtolower(trim($code));

        if ($code === '') {
            throw new RuntimeException(
                'Payment provider code is required.'
            );
        }

        return $code;
    }
}