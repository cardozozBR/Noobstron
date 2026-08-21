<?php

namespace Tests\Unit;

use App\Support\PaymentProviderResult;
use PHPUnit\Framework\TestCase;

class PaymentProviderContractTest extends TestCase
{
    public function test_success_result_can_hold_external_reference(): void
    {
        $result = PaymentProviderResult::success(
            'provider-123'
        );

        $this->assertTrue($result->successful);
        $this->assertSame(
            'provider-123',
            $result->externalReference
        );
        $this->assertNull($result->failureReason);
    }

    public function test_success_result_can_hold_checkout_url(): void
    {
        $result = PaymentProviderResult::success(
            'provider-456',
            'https://payments.example.test/checkout/456'
        );

        $this->assertTrue($result->successful);
        $this->assertSame(
            'https://payments.example.test/checkout/456',
            $result->checkoutUrl
        );
    }

    public function test_failure_result_contains_reason(): void
    {
        $result = PaymentProviderResult::failure(
            '  Provider unavailable  '
        );

        $this->assertFalse($result->successful);
        $this->assertSame(
            'Provider unavailable',
            $result->failureReason
        );
        $this->assertNull($result->checkoutUrl);
    }
}