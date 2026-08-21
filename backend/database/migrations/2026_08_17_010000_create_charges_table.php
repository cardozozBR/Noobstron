<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'charges',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('receivable_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'status',
                    30
                )->default('pending');

                $table->unsignedInteger(
                    'attempt'
                )->default(1);

                $table->timestampTz(
                    'scheduled_at'
                )->nullable();

                $table->timestampTz(
                    'sent_at'
                )->nullable();

                $table->timestampTz(
                    'paid_at'
                )->nullable();

                $table->timestampTz(
                    'failed_at'
                )->nullable();

                $table->timestampTz(
                    'cancelled_at'
                )->nullable();

                $table->string(
                    'channel',
                    50
                )->nullable();

                $table->string(
                    'recipient'
                )->nullable();

                $table->string(
                    'external_reference'
                )->nullable();

                $table->text(
                    'failure_reason'
                )->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'tenant_id',
                        'status',
                        'scheduled_at',
                    ],
                    'charges_tenant_status_schedule_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'receivable_id',
                        'attempt',
                    ],
                    'charges_tenant_receivable_attempt_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'sent_at',
                    ],
                    'charges_tenant_sent_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'charges'
        );
    }
};