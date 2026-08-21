<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->text('description')
                ->nullable();

            $table->boolean('is_default')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'name'],
                'pipelines_tenant_name_unique'
            );

            $table->index(
                ['tenant_id', 'is_default'],
                'pipelines_tenant_default_index'
            );

            $table->index(
                ['tenant_id', 'is_active'],
                'pipelines_tenant_active_index'
            );
        });

        DB::statement(
            'CREATE UNIQUE INDEX pipelines_one_default_per_tenant_unique
             ON pipelines (tenant_id)
             WHERE is_default = true'
        );

        Schema::create('pipeline_stages', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pipeline_id')
                ->constrained('pipelines')
                ->cascadeOnDelete();

            $table->string('name');
            $table->unsignedInteger('position');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique(
                ['pipeline_id', 'name'],
                'pipeline_stages_pipeline_name_unique'
            );

            $table->unique(
                ['pipeline_id', 'position'],
                'pipeline_stages_pipeline_position_unique'
            );

            $table->index(
                ['tenant_id', 'pipeline_id'],
                'pipeline_stages_tenant_pipeline_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
    }
};
