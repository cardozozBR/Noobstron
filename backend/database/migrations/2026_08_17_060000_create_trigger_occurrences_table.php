<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'trigger_occurrences',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'tenant_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'trigger_name',
                    120
                );

                $table->string(
                    'subject_type',
                    191
                );

                $table->string(
                    'subject_id',
                    191
                );

                $table->string(
                    'boundary',
                    191
                );

                $table->timestampTz(
                    'occurred_at'
                );

                $table->timestampsTz();

                $table->unique(
                    [
                        'tenant_id',
                        'trigger_name',
                        'subject_type',
                        'subject_id',
                        'boundary',
                    ],
                    'trigger_occurrences_identity_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'trigger_name',
                        'occurred_at',
                    ],
                    'trigger_occurrences_tenant_trigger_time_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'trigger_occurrences'
        );
    }
};