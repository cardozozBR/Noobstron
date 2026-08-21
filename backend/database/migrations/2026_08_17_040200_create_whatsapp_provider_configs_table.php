<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'whatsapp_provider_configs',
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
                    'provider',
                    64
                );

                $table->string(
                    'sender_id'
                );

                $table->text(
                    'settings'
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
                    'provider',
                ]);

                $table->index([
                    'tenant_id',
                    'active',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'whatsapp_provider_configs'
        );
    }
};