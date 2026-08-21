<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'receivables',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('customer_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->foreignId('sale_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->string('title');

                $table->string(
                    'currency',
                    3
                );

                $table->unsignedBigInteger(
                    'amount_minor'
                );

                $table->date('due_date');

                $table->string(
                    'status',
                    30
                )->default('pending');

                $table->timestampTz(
                    'paid_at'
                )->nullable();

                $table->string(
                    'payment_reference'
                )->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'tenant_id',
                        'status',
                        'due_date',
                    ],
                    'receivables_tenant_status_due_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'customer_id',
                        'due_date',
                    ],
                    'receivables_tenant_customer_due_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'sale_id',
                    ],
                    'receivables_tenant_sale_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'receivables'
        );
    }
};