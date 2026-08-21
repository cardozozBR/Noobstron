<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('converted_customer_id')
                ->nullable()
                ->after('responsible_user_id')
                ->constrained('customers')
                ->nullOnDelete();

            $table->timestamp('converted_at')
                ->nullable()
                ->after('converted_customer_id');

            $table->unique(
                [
                    'tenant_id',
                    'converted_customer_id',
                ],
                'leads_tenant_converted_customer_unique'
            );

            $table->index(
                [
                    'tenant_id',
                    'converted_at',
                ],
                'leads_tenant_converted_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(
                'leads_tenant_converted_at_index'
            );

            $table->dropUnique(
                'leads_tenant_converted_customer_unique'
            );

            $table->dropConstrainedForeignId(
                'converted_customer_id'
            );

            $table->dropColumn(
                'converted_at'
            );
        });
    }
};
