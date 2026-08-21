<?php

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('plan_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status', 32)
                ->default(
                    SubscriptionStatus::ACTIVE->value
                );

            $table->timestamp('current_period_start');

            $table->timestamp('current_period_end');

            $table->timestamps();

            $table->index(
                [
                    'tenant_id',
                    'status',
                ],
                'subscriptions_tenant_status_index'
            );

            $table->index(
                'current_period_end',
                'subscriptions_period_end_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'subscriptions'
        );
    }
};