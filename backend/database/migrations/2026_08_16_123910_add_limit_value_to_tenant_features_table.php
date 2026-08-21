<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_features', function (Blueprint $table) {
            $table->unsignedBigInteger('limit_value')
                ->nullable()
                ->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_features', function (Blueprint $table) {
            $table->dropColumn('limit_value');
        });
    }
};