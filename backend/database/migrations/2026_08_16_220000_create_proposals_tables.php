<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('opportunity_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('number', 100);

            $table->string('status', 20)
                ->default('draft');

            $table->char('currency', 3);

            $table->date('valid_until')
                ->nullable();

            $table->unsignedBigInteger('subtotal_minor')
                ->default(0);

            $table->unsignedBigInteger('discount_minor')
                ->default(0);

            $table->unsignedBigInteger('tax_minor')
                ->default(0);

            $table->unsignedBigInteger('total_minor')
                ->default(0);

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'tenant_id',
                    'number',
                ],
                'proposals_tenant_number_unique'
            );

            $table->index(
                [
                    'tenant_id',
                    'status',
                    'valid_until',
                ],
                'proposals_tenant_status_valid_index'
            );
        });

        Schema::create('proposal_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('proposal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('catalog_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedInteger('position');

            $table->string('item_type', 20);

            $table->string('name');

            $table->string('code', 100)
                ->nullable();

            $table->decimal(
                'quantity',
                18,
                4
            );

            $table->unsignedBigInteger(
                'unit_price_minor'
            );

            $table->unsignedBigInteger(
                'discount_minor'
            )->default(0);

            $table->unsignedBigInteger(
                'tax_minor'
            )->default(0);

            $table->unsignedBigInteger(
                'total_minor'
            )->default(0);

            $table->json('taxes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'proposal_id',
                    'position',
                ],
                'proposal_items_proposal_position_unique'
            );

            $table->index(
                [
                    'tenant_id',
                    'proposal_id',
                ],
                'proposal_items_tenant_proposal_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'proposal_items'
        );

        Schema::dropIfExists(
            'proposals'
        );
    }
};
