<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('feature');
            $table->boolean('enabled')->default(false);
            $table->unsignedBigInteger('limit_value')->nullable();
            $table->timestamps();

            $table->unique([
                'plan_id',
                'feature',
            ]);
        });

        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->timestamps();

            $table->unique([
                'plan_id',
                'currency',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
