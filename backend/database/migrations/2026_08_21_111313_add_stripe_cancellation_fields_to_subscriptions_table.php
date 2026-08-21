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
                $table->timestamp('cancel_at')
                    ->nullable()
                    ->after('paid_at');

                $table->timestamp('canceled_at')
                    ->nullable()
                    ->after('cancel_at');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'subscriptions',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'cancel_at',
                    'canceled_at',
                ]);
            }
        );
    }
};