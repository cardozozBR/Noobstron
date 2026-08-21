<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "
            CREATE UNIQUE INDEX
                subscriptions_one_current_per_tenant_unique
            ON subscriptions (tenant_id)
            WHERE status IN ('active', 'suspended')
            "
        );
    }

    public function down(): void
    {
        DB::statement(
            "
            DROP INDEX IF EXISTS
                subscriptions_one_current_per_tenant_unique
            "
        );
    }
};