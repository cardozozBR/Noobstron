<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'charge_recurrences',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('receivable_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'frequency',
                    30
                )->default('monthly');

                $table->unsignedInteger(
                    'interval_count'
                )->default(1);

                $table->timestampTz(
                    'next_run_at'
                );

                $table->timestampTz(
                    'ends_at'
                )->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->string(
                    'channel',
                    50
                )->nullable();

                $table->string(
                    'recipient'
                )->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'tenant_id',
                        'is_active',
                        'next_run_at',
                    ],
                    'charge_recurrences_due_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'receivable_id',
                    ],
                    'charge_recurrences_receivable_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'charge_recurrences'
        );
    }
};