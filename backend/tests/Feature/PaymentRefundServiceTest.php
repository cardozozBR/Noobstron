<?php

namespace Tests\Feature;

use App\Contracts\PaymentProvider;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ChargeService;
use App\Services\PaymentProviderRegistry;
use App\Services\PaymentRefundService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\PaymentProviderResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentRefundServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_charge_can_be_refunded(): void
    {
        $charge = $this->paidCharge();

        $this->register(
            PaymentProviderResult::success(
                'refund-123'
            )
        );

        $result = app(
            PaymentRefundService::class
        )->refund(
            $charge,
            'test'
        );

        $this->assertTrue($result->successful);

        $this->assertSame(
            'refund-123',
            $result->externalReference
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $charge->tenant_id,
                'action' => 'payment.refunded',
            ]
        );
    }

    public function test_non_paid_charge_cannot_be_refunded(): void
    {
        $charge = $this->pendingCharge();

        $this->register(
            PaymentProviderResult::success(
                'refund-123'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            PaymentRefundService::class
        )->refund(
            $charge,
            'test'
        );
    }

    public function test_provider_refund_failure_is_rejected(): void
    {
        $charge = $this->paidCharge();

        $this->register(
            PaymentProviderResult::failure(
                'Refund rejected'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            PaymentRefundService::class
        )->refund(
            $charge,
            'test'
        );
    }

    private function register(
        PaymentProviderResult $refundResult
    ): void {
        app(PaymentProviderRegistry::class)
            ->register(
                'test',
                new class($refundResult)
                    implements PaymentProvider {
                    public function __construct(
                        private PaymentProviderResult $refundResult
                    ) {
                    }

                    public function checkout(
                        Charge $charge
                    ): PaymentProviderResult {
                        return PaymentProviderResult::success();
                    }

                    public function refund(
                        Charge $charge
                    ): PaymentProviderResult {
                        return $this->refundResult;
                    }
                }
            );
    }

    private function paidCharge(): Charge
    {
        $charge = $this->pendingCharge();

        app(
            ReceivableService::class
        )->markPaid(
            $charge->receivable,
            'PAYMENT-PAID'
        );

        app(
            ChargeService::class
        )->syncReceivablePaid(
            $charge->receivable->refresh()
        );

        return $charge->refresh();
    }

    private function pendingCharge(): Charge
    {
        $tenant = Tenant::query()->create([
            'name' => 'Refund Tenant',
            'slug' => uniqid('refund-', true),
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Refund Customer',
            'type' => 'company',
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Refund',
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