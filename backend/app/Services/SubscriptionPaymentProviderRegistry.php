<?php

namespace App\Services;

use App\Contracts\SubscriptionPaymentProvider;
use RuntimeException;

class SubscriptionPaymentProviderRegistry
{
    /**
     * @var array<string, SubscriptionPaymentProvider>
     */
    private array $providers = [];

    public function register(
        SubscriptionPaymentProvider $provider
    ): void {
        $code = strtolower(
            trim($provider->code())
        );

        if ($code === '') {
            throw new RuntimeException(
                'Subscription payment provider code is required.'
            );
        }

        if (isset($this->providers[$code])) {
            throw new RuntimeException(
                'Subscription payment provider already registered.'
            );
        }

        $this->providers[$code] = $provider;
    }

    public function resolve(
        string $code
    ): SubscriptionPaymentProvider {
        $code = strtolower(trim($code));

        if ($code === '') {
            throw new RuntimeException(
                'Subscription payment provider code is required.'
            );
        }

        if (! isset($this->providers[$code])) {
            throw new RuntimeException(
                'Subscription payment provider is not registered.'
            );
        }

        return $this->providers[$code];
    }

    public function has(
        string $code
    ): bool {
        $code = strtolower(trim($code));

        if ($code === '') {
            return false;
        }

        return isset(
            $this->providers[$code]
        );
    }
}