<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'payment_event_receipts',
            function (Blueprint $table): void {
                $table->string(
                    'status',
                    40
                )
                    ->default('processed')
                    ->after('external_reference');

                $table->unsignedInteger(
                    'attempts'
                )
                    ->default(1)
                    ->after('status');

                $table->text(
                    'last_error'
                )
                    ->nullable()
                    ->after('attempts');

                $table->timestampTz(
                    'processed_at'
                )
                    ->nullable()
                    ->change();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'payment_event_receipts',
            function (Blueprint $table): void {
                $table->timestampTz(
                    'processed_at'
                )
                    ->nullable(false)
                    ->change();

                $table->dropColumn([
                    'status',
                    'attempts',
                    'last_error',
                ]);
            }
        );
    }
};