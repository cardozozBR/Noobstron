<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'imports',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->string('target', 32)
                    ->nullable();

                $table->string('original_name');
                $table->string('stored_path');

                $table->string('mime_type', 191)
                    ->nullable();

                $table->unsignedBigInteger('size')
                    ->nullable();

                $table->string(
                    'status',
                    32
                )->default('uploaded');

                $table->string(
                    'delimiter',
                    8
                )->default(',');

                $table->string(
                    'encoding',
                    32
                )->default('UTF-8');

                $table->json('header')
                    ->nullable();

                $table->json('mapping')
                    ->nullable();

                $table->unsignedInteger('row_count')
                    ->nullable();

                $table->unsignedInteger('processed_count')
                    ->default(0);

                $table->unsignedInteger('success_count')
                    ->default(0);

                $table->unsignedInteger('failure_count')
                    ->default(0);

                $table->text('error_message')
                    ->nullable();

                $table->timestamp('started_at')
                    ->nullable();

                $table->timestamp('completed_at')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'tenant_id',
                        'status',
                    ],
                    'imports_tenant_status_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'created_at',
                    ],
                    'imports_tenant_created_at_index'
                );
            }
        );

        Schema::create(
            'import_rows',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('import_id')
                    ->constrained('imports')
                    ->cascadeOnDelete();

                $table->unsignedInteger('line');

                $table->string(
                    'status',
                    32
                );

                $table->json('data')
                    ->nullable();

                $table->json('errors')
                    ->nullable();

                $table->string(
                    'entity_type',
                    191
                )->nullable();

                $table->unsignedBigInteger(
                    'entity_id'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'import_id',
                        'line',
                    ],
                    'import_rows_import_line_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'status',
                    ],
                    'import_rows_tenant_status_index'
                );

                $table->index(
                    [
                        'entity_type',
                        'entity_id',
                    ],
                    'import_rows_entity_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'import_rows'
        );

        Schema::dropIfExists(
            'imports'
        );
    }
};
