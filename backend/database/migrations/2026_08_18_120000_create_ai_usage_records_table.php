<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('provider', 64);
            $table->string('model', 128);
            $table->string('operation', 100)->nullable();

            $table->unsignedBigInteger('input_tokens')
                ->default(0);

            $table->unsignedBigInteger('output_tokens')
                ->default(0);

            $table->unsignedBigInteger('total_tokens')
                ->default(0);

            $table->timestamps();

            $table->index(
                ['tenant_id', 'created_at'],
                'ai_usage_records_tenant_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_records');
    }
};