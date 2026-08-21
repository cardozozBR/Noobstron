<?php

namespace Tests\Unit;

use App\Contracts\PaymentProvider;
use App\Models\Charge;
use App\Services\PaymentProviderRegistry;
use App\Support\PaymentProviderResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PaymentProviderRegistryTest extends TestCase
{
    public function test_provider_can_be_registered_and_resolved(): void
    {
        $registry = new PaymentProviderRegistry();

        $provider = $this->provider();

        $registry->register(
            'test-provider',
            $provider
        );

        $this->assertSame(
            $provider,
            $registry->resolve('test-provider')
        );
    }

    public function test_provider_code_is_normalized(): void
    {
        $registry = new PaymentProviderRegistry();

        $provider = $this->provider();

        $registry->register(
            ' Test-Provider ',
            $provider
        );

        $this->assertTrue(
            $registry->has('test-provider')
        );

        $this->assertSame(
            $provider,
            $registry->resolve('TEST-PROVIDER')
        );
    }

    public function test_duplicate_provider_is_rejected(): void
    {
        $registry = new PaymentProviderRegistry();

        $registry->register(
            'test',
            $this->provider()
        );

        $this->expectException(
            RuntimeException::class
        );

        $registry->register(
            'test',
            $this->provider()
        );
    }

    public function test_unknown_provider_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        (new PaymentProviderRegistry())
            ->resolve('missing');
    }

    public function test_blank_provider_code_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        (new PaymentProviderRegistry())
            ->register(
                '   ',
                $this->provider()
            );
    }

    private function provider(): PaymentProvider
    {
        return new class implements PaymentProvider
        {
            public function checkout(
                Charge $charge
            ): PaymentProviderResult {
                return PaymentProviderResult::success(
                    'test-checkout'
                );
            }

            public function refund(
                Charge $charge
            ): PaymentProviderResult {
                return PaymentProviderResult::success(
                    'test-refund'
                );
            }
        };
    }
}