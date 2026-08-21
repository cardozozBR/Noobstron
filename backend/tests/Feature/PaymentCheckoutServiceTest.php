<?php

namespace Tests\Feature;

use App\Contracts\PaymentProvider;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ChargeService;
use App\Services\PaymentCheckoutService;
use App\Services\PaymentProviderRegistry;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\PaymentProviderResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_checkout_marks_charge_as_sent(): void
    {
        $charge = $this->charge();

        $this->register(
            PaymentProviderResult::success(
                'provider-123',
                'https://checkout.test/123'
            )
        );

        $result = app(
            PaymentCheckoutService::class
        )->checkout(
            $charge,
            'test'
        );

        $charge->refresh();

        $this->assertTrue($result->successful);
        $this->assertSame(
            'sent',
            $charge->status->value
        );
        $this->assertSame(
            'provider-123',
            $charge->external_reference
        );
    }

    public function test_failed_checkout_marks_charge_as_failed(): void
    {
        $charge = $this->charge();

        $this->register(
            PaymentProviderResult::failure(
                'Provider unavailable'
            )
        );

        $result = app(
            PaymentCheckoutService::class
        )->checkout(
            $charge,
            'test'
        );

        $charge->refresh();

        $this->assertFalse($result->successful);
        $this->assertSame(
            'failed',
            $charge->status->value
        );
        $this->assertSame(
            'Provider unavailable',
            $charge->failure_reason
        );
    }

    private function register(
        PaymentProviderResult $result
    ): void {
        app(PaymentProviderRegistry::class)
            ->register(
                'test',
                new class($result)
                    implements PaymentProvider {
                    public function __construct(
                        private PaymentProviderResult $result
                    ) {
                    }

                    public function checkout(
                        Charge $charge
                    ): PaymentProviderResult {
                        return $this->result;
                    }

                    public function refund(
                        Charge $charge
                    ): PaymentProviderResult {
                        return PaymentProviderResult::success();
                    }
                }
            );
    }

    private function charge(): Charge
    {
        $tenant = Tenant::query()->create([
            'name' => 'Checkout Tenant',
            'slug' => uniqid('checkout-', true),
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Checkout Customer',
            'type' => 'company',
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Checkout',
            'currency' => 'BRL',
            'amount_minor' => 1000,
            'due_date' => now()->toDateString(),
        ]);

        return app(
            ChargeService::class
        )->create([
            'receivable_id' => $receivable->id,
        ]);
    }
}