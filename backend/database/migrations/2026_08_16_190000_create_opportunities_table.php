<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'opportunities',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('name');

                $table->foreignId('customer_id')
                    ->constrained('customers')
                    ->restrictOnDelete();

                $table->foreignId('pipeline_id')
                    ->constrained('pipelines')
                    ->restrictOnDelete();

                $table->foreignId('pipeline_stage_id')
                    ->constrained('pipeline_stages')
                    ->restrictOnDelete();

                $table->foreignId('responsible_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->bigInteger('value_minor')
                    ->default(0);

                $table->string('currency', 3);

                $table->unsignedSmallInteger(
                    'probability'
                )->default(0);

                $table->date(
                    'expected_close_date'
                )->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'tenant_id',
                        'pipeline_id',
                        'pipeline_stage_id',
                    ],
                    'opportunities_tenant_pipeline_stage_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'customer_id',
                    ],
                    'opportunities_tenant_customer_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'responsible_user_id',
                    ],
                    'opportunities_tenant_responsible_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'expected_close_date',
                    ],
                    'opportunities_tenant_close_date_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'opportunities'
        );
    }
};
