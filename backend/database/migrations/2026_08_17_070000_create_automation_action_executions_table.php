<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'automation_action_executions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'tenant_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'execution_key',
                    191
                );

                $table->string(
                    'action_type',
                    120
                );

                $table->timestampTz(
                    'completed_at'
                )->nullable();

                $table->timestampsTz();

                $table->unique(
                    [
                        'tenant_id',
                        'execution_key',
                    ],
                    'automation_action_executions_identity_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'action_type',
                        'created_at',
                    ],
                    'automation_action_executions_type_time_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'automation_action_executions'
        );
    }
};