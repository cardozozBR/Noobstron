<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'subscriptions',
            function (Blueprint $table): void {
                $table->string(
                    'payment_provider',
                    32
                )->nullable()->after('status');

                $table->string(
                    'external_reference',
                    255
                )->nullable()->after('payment_provider');

                $table->string(
                    'payment_method',
                    32
                )->nullable()->after('external_reference');

                $table->timestamp(
                    'paid_at'
                )->nullable()->after('payment_method');

                $table->index(
                    [
                        'payment_provider',
                        'external_reference',
                    ],
                    'subscriptions_payment_reference_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'subscriptions',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'subscriptions_payment_reference_index'
                );

                $table->dropColumn([
                    'payment_provider',
                    'external_reference',
                    'payment_method',
                    'paid_at',
                ]);
            }
        );
    }
};
