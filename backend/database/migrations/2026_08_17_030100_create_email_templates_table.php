<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'email_templates',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'name'
                );

                $table->string(
                    'subject_template'
                );

                $table->text(
                    'body_template'
                );

                $table->timestamps();

                $table->unique([
                    'tenant_id',
                    'name',
                ]);

                $table->index([
                    'tenant_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'email_templates'
        );
    }
};