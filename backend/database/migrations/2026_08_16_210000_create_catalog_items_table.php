<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 20);

            $table->string('name');

            $table->string('code', 100)
                ->nullable();

            $table->unsignedBigInteger('price_minor')
                ->default(0);

            $table->char('currency', 3);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique(
                [
                    'tenant_id',
                    'code',
                ],
                'catalog_items_tenant_code_unique'
            );

            $table->index(
                [
                    'tenant_id',
                    'type',
                    'is_active',
                ],
                'catalog_items_tenant_type_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'catalog_items'
        );
    }
};
