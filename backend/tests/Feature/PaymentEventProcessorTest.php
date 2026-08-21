<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ChargeService;
use App\Services\PaymentEventProcessor;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\PaymentProviderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentEventProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_event_marks_receivable_and_charge_paid(): void
    {
        $charge = $this->sentCharge(
            'provider-approved'
        );

        app(PaymentEventProcessor::class)->process(
            new PaymentProviderEvent(
                'evt-approved',
                'payment.approved',
                'provider-approved'
            )
        );

        $this->assertSame(
            'paid',
            $charge->refresh()->status->value
        );

        $this->assertSame(
            'paid',
            $charge->receivable
                ->refresh()
                ->status
                ->value
        );
    }

    public function test_failed_event_marks_charge_failed(): void
    {
        $charge = $this->sentCharge(
            'provider-failed'
        );

        app(PaymentEventProcessor::class)->process(
            new PaymentProviderEvent(
                'evt-failed',
                'payment.failed',
                'provider-failed',
                'Declined'
            )
        );

        $charge->refresh();

        $this->assertSame(
            'failed',
            $charge->status->value
        );

        $this->assertSame(
            'Declined',
            $charge->failure_reason
        );
    }

    public function test_unknown_external_reference_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(PaymentEventProcessor::class)->process(
            new PaymentProviderEvent(
                'evt-missing',
                'payment.approved',
                'missing-reference'
            )
        );
    }

    public function test_unknown_event_type_is_rejected(): void
    {
        $this->sentCharge(
            'provider-unknown'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(PaymentEventProcessor::class)->process(
            new PaymentProviderEvent(
                'evt-unknown',
                'payment.unknown',
                'provider-unknown'
            )
        );
    }

    private function sentCharge(
        string $reference
    ): Charge {
        $tenant = Tenant::query()->create([
            'name' => 'Payment Event Tenant',
            'slug' => uniqid('payment-event-', true),
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Payment Event Customer',
            'type' => 'company',
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Payment Event',
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