<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 32);
            $table->string('status', 32)
                ->default('pending');

            $table->string('title');

            $table->text('description')
                ->nullable();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('opportunity_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('due_at')
                ->nullable();

            $table->dateTime('completed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'tenant_id',
                'status',
                'due_at',
            ]);

            $table->index([
                'tenant_id',
                'responsible_user_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'customer_id',
            ]);

            $table->index([
                'tenant_id',
                'opportunity_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
