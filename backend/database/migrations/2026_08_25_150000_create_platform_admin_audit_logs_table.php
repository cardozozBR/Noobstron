<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'platform_admin_audit_logs',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('platform_admin_id')
                    ->nullable()
                    ->constrained('platform_admins')
                    ->nullOnDelete();

                $table->foreignId('tenant_id')
                    ->nullable()
                    ->constrained('tenants')
                    ->nullOnDelete();

                $table->string('action', 150);

                $table->string('entity_type', 150)
                    ->nullable();

                $table->string('entity_id', 191)
                    ->nullable();

                $table->json('before_state')
                    ->nullable();

                $table->json('after_state')
                    ->nullable();

                $table->string('ip_address', 45)
                    ->nullable();

                $table->string('result', 50)
                    ->default('success');

                $table->text('reason')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    ['platform_admin_id', 'created_at'],
                    'platform_admin_audit_admin_created_index'
                );

                $table->index(
                    ['tenant_id', 'created_at'],
                    'platform_admin_audit_tenant_created_index'
                );

                $table->index(
                    ['action', 'created_at'],
                    'platform_admin_audit_action_created_index'
                );

                $table->index(
                    ['entity_type', 'entity_id'],
                    'platform_admin_audit_entity_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'platform_admin_audit_logs'
        );
    }
};
