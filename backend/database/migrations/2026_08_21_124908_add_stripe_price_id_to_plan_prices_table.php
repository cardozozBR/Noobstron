<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'plan_prices',
            function (Blueprint $table): void {
                $table->string(
                    'stripe_price_id'
                )
                    ->nullable()
                    ->after('amount_minor');

                $table->unique(
                    'stripe_price_id',
                    'plan_prices_stripe_price_id_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'plan_prices',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'plan_prices_stripe_price_id_unique'
                );

                $table->dropColumn(
                    'stripe_price_id'
                );
            }
        );
    }
};