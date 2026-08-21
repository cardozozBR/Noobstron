<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'payment_event_receipts',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'provider',
                    80
                );

                $table->string(
                    'event_id',
                    191
                );

                $table->string(
                    'event_type',
                    120
                );

                $table->string(
                    'external_reference',
                    191
                );

                $table->timestampTz(
                    'processed_at'
                );

                $table->timestamps();

                $table->unique(
                    [
                        'provider',
                        'event_id',
                    ],
                    'payment_event_receipts_provider_event_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'payment_event_receipts'
        );
    }
};