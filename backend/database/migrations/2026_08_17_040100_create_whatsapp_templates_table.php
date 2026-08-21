<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'whatsapp_templates',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table->foreignId(
                    'tenant_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'name'
                );

                $table->text(
                    'body_template'
                );

                $table->string(
                    'provider',
                    64
                )
                    ->nullable();

                $table->string(
                    'provider_template_name'
                )
                    ->nullable();

                $table->string(
                    'language',
                    16
                )
                    ->nullable();

                $table->boolean(
                    'active'
                )
                    ->default(
                        true
                    );

                $table->timestampsTz();

                $table->unique([
                    'tenant_id',
                    'name',
                ]);

                $table->index([
                    'tenant_id',
                    'active',
                ]);

                $table->index([
                    'tenant_id',
                    'provider',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'whatsapp_templates'
        );
    }
};