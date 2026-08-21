<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('status', 50)
                ->default('new');

            $table->string('source', 50)
                ->default('manual');

            $table->json('tags')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'name'],
                'leads_tenant_name_index'
            );

            $table->index(
                ['tenant_id', 'status'],
                'leads_tenant_status_index'
            );

            $table->index(
                ['tenant_id', 'responsible_user_id'],
                'leads_tenant_responsible_index'
            );

            $table->index(
                ['tenant_id', 'created_at'],
                'leads_tenant_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
