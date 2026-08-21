<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'name'],
                'users_tenant_id_name_index'
            );
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'action', 'created_at'],
                'audit_logs_tenant_action_created_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(
                'audit_logs_tenant_action_created_at_index'
            );
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(
                'users_tenant_id_name_index'
            );
        });
    }
};