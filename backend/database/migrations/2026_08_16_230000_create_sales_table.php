<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('opportunity_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('proposal_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('number', 100);

            $table->string('currency', 3);
            $table->unsignedBigInteger('total_minor');

            $table->timestampTz('closed_at');

            $table->string('customer_name');
            $table->string('opportunity_title');
            $table->string('proposal_number')->nullable();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'number'],
                'sales_tenant_number_unique'
            );

            $table->unique(
                ['tenant_id', 'opportunity_id'],
                'sales_tenant_opportunity_unique'
            );

            $table->index(
                ['tenant_id', 'closed_at'],
                'sales_tenant_closed_at_index'
            );

            $table->index(
                ['tenant_id', 'customer_id'],
                'sales_tenant_customer_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};