<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'commercial_contacts',
            function (Blueprint $table): void {
                $table->foreignId('converted_tenant_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('tenants')
                    ->nullOnDelete();

                $table->timestamp('converted_at')
                    ->nullable()
                    ->after('converted_tenant_id');

                $table->index(
                    ['converted_tenant_id', 'converted_at'],
                    'commercial_contacts_conversion_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'commercial_contacts',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'commercial_contacts_conversion_index'
                );

                $table->dropConstrainedForeignId(
                    'converted_tenant_id'
                );

                $table->dropColumn('converted_at');
            }
        );
    }
};