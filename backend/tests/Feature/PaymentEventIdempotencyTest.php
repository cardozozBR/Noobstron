<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Customer;
use App\Models\PaymentEventReceipt;
use App\Models\Tenant;
use App\Services\ChargeService;
use App\Services\PaymentEventProcessor;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\PaymentProviderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentEventIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_event_is_processed_once(): void
    {
        $charge = $this->sentCharge(
            'provider-idempotent'
        );

        $event = new PaymentProviderEvent(
            'evt-001',
            'payment.approved',
            'provider-idempotent'
        );

        $processor = app(
            PaymentEventProcessor::class
        );

        $processor->process(
            $event,
            'test'
        );

        $processor->process(
            $event,
            'test'
        );

        $this->assertSame(
            1,
            PaymentEventReceipt::query()->count()
        );

        $this->assertSame(
            'paid',
            $charge->refresh()->status->value
        );
    }

    public function test_same_event_id_can_exist_for_different_providers(): void
    {
        $processor = app(
            PaymentEventProcessor::class
        );

        $first = $this->sentCharge(
            'provider-a'
        );

        $processor->process(
            new PaymentProviderEvent(
                'evt-shared',
                'payment.approved',
                'provider-a'
            ),
            'provider-a'
        );

        $second = $this->sentCharge(
            'provider-b'
        );

        $processor->process(
            new PaymentProviderEvent(
                'evt-shared',
                'payment.approved',
                'provider-b'
            ),
            'provider-b'
        );

        $this->assertSame(
            2,
            PaymentEventReceipt::query()->count()
        );

        $this->assertSame(
            'paid',
            $first->refresh()->status->value
        );

        $this->assertSame(
            'paid',
            $second->refresh()->status->value
        );
    }

    private function sentCharge(
        string $reference
    ): Charge {
        $tenant = Tenant::query()->create([
            'name' => 'Payment Idempotency Tenant',
            'slug' => uniqid(
                'payment-idempotency-',
                true
            ),
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Payment Idempotency Customer',
            'type' => 'company',
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Payment Idempotency',
            'currency' => 'BRL',
            'amount_minor' => 1000,
            'due_date' => now()->toDateString(),
        ]);

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' => $receivable->id,
        ]);

        return app(
            ChargeService::class
        )->markSent(
            $charge,
            $reference
        );
    }
}