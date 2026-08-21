<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\PaymentEventReceipt;
use App\Support\PaymentProviderEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentEventProcessor
{
    public function __construct(
        private readonly ChargeService $charges,
        private readonly ReceivableService $receivables,
    ) {
    }

    public function process(
        PaymentProviderEvent $event,
        string $provider = 'default',
    ): Charge {
        $provider = strtolower(trim($provider));

        if ($provider === '') {
            throw new RuntimeException(
                'Payment provider code is required.'
            );
        }

        return DB::transaction(
            function () use (
                $event,
                $provider
            ): Charge {
                $existing = PaymentEventReceipt::query()
                    ->where('provider', $provider)
                    ->where('event_id', $event->eventId)
                    ->first();

                if ($existing !== null) {
                    $charge = Charge::query()
                        ->where(
                            'external_reference',
                            $event->externalReference
                        )
                        ->first();

                    if ($charge === null) {
                        throw new RuntimeException(
                            'Payment charge was not found.'
                        );
                    }

                    return $charge;
                }

                $charge = $this->processEvent(
                    $event
                );

                PaymentEventReceipt::query()->create([
                    'provider' => $provider,
                    'event_id' => $event->eventId,
                    'event_type' => $event->normalizedType(),
                    'external_reference' =>
                        $event->externalReference,
                    'processed_at' => now(),
                ]);

                return $charge;
            }
        );
    }

    private function processEvent(
        PaymentProviderEvent $event
    ): Charge {
        $charge = Charge::query()
            ->where(
                'external_reference',
                $event->externalReference
            )
            ->first();

        if ($charge === null) {
            throw new RuntimeException(
                'Payment charge was not found.'
            );
        }

        return match ($event->normalizedType()) {
            'payment.approved' =>
                $this->approve($charge, $event),

            'payment.failed' =>
                $this->fail($charge, $event),

            default => throw new RuntimeException(
                'Unsupported payment event type.'
            ),
        };
    }

    private function approve(
        Charge $charge,
        PaymentProviderEvent $event,
    ): Charge {
        $receivable = $this->receivables->markPaid(
            $charge->receivable,
            $event->externalReference
        );

        $this->charges->syncReceivablePaid(
            $receivable
        );

        return $charge->refresh();
    }

    private function fail(
        Charge $charge,
        PaymentProviderEvent $event,
    ): Charge {
        return $this->charges->markFailed(
            $charge,
            $event->failureReason
                ?? 'Payment failed.'
        );
    }
}