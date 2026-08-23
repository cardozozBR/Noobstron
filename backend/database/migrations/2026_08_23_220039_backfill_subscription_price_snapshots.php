<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscriptions')
            ->whereNull('amount_minor')
            ->orderBy('id')
            ->get()
            ->each(function ($subscription): void {
                $invoice = DB::table('subscription_invoices')
                    ->where(
                        'subscription_id',
                        $subscription->id
                    )
                    ->where('status', 'paid')
                    ->where(
                        'billing_reason',
                        'subscription_create'
                    )
                    ->whereNotNull('currency')
                    ->where('amount_paid', '>', 0)
                    ->orderBy('paid_at')
                    ->orderBy('id')
                    ->first();

                if ($invoice === null) {
                    return;
                }

                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'currency' => strtoupper(
                            (string) $invoice->currency
                        ),
                        'amount_minor' =>
                            (int) $invoice->amount_paid,
                    ]);
            });
    }

    public function down(): void
{
    // Nao apagamos snapshots financeiros no rollback,
    // pois nao e possivel distinguir com seguranca
    // valores historicos dos gravados depois.
}
};