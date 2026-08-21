<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_invoices',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'subscription_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'provider',
                    32
                );

                $table->string(
                    'external_invoice_id'
                );

                $table->string(
                    'status',
                    32
                )->nullable();

                $table->string(
                    'currency',
                    8
                )->nullable();

                $table->bigInteger(
                    'amount_due'
                )->default(0);

                $table->bigInteger(
                    'amount_paid'
                )->default(0);

                $table->bigInteger(
                    'amount_remaining'
                )->default(0);

                $table->string(
                    'billing_reason',
                    64
                )->nullable();

                $table->timestamp(
                    'period_start'
                )->nullable();

                $table->timestamp(
                    'period_end'
                )->nullable();

                $table->timestamp(
                    'paid_at'
                )->nullable();

                $table->text(
                    'hosted_invoice_url'
                )->nullable();

                $table->text(
                    'invoice_pdf'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'provider',
                        'external_invoice_id',
                    ],
                    'subscription_invoices_provider_external_unique'
                );

                $table->index(
                    [
                        'subscription_id',
                        'status',
                    ],
                    'subscription_invoices_subscription_status_index'
                );

                $table->index(
                    'period_end',
                    'subscription_invoices_period_end_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'subscription_invoices'
        );
    }
};