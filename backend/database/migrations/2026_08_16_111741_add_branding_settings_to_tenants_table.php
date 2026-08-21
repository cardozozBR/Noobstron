<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('brand_primary_color', 7)
                ->nullable()
                ->after('currency');

            $table->string('logo_path')
                ->nullable()
                ->after('brand_primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'brand_primary_color',
                'logo_path',
            ]);
        });
    }
};