<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\StripeSubscriptionInvoiceSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncStripeSubscriptionInvoices extends Command
{
    protected $signature =
        'subscriptions:sync-stripe-invoices';

    protected $description =
        'Sincroniza faturas das assinaturas Stripe.';

    public function handle(
        StripeSubscriptionInvoiceSyncService $syncService
    ): int {
        $subscriptions = Subscription::withoutGlobalScopes()
            ->where(
                'payment_provider',
                'stripe'
            )
            ->whereNotNull(
                'external_reference'
            )
            ->orderBy('id')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info(
                'Nenhuma assinatura Stripe encontrada.'
            );

            return self::SUCCESS;
        }

        $processed = 0;
        $syncedInvoices = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $count = $syncService->sync(
                    $subscription
                );

                $processed++;
                $syncedInvoices += $count;

                $this->line(
                    'Subscription #'
                    . $subscription->id
                    . ': '
                    . $count
                    . ' invoice(s) sincronizada(s).'
                );
            } catch (Throwable $exception) {
                $failed++;

                $this->error(
                    'Subscription #'
                    . $subscription->id
                    . ': '
                    . $exception->getMessage()
                );
            }
        }

        $this->newLine();

        $this->info(
            'Assinaturas processadas: '
            . $processed
        );

        $this->info(
            'Invoices sincronizadas: '
            . $syncedInvoices
        );

        if ($failed > 0) {
            $this->warn(
                'Falhas: '
                . $failed
            );
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}