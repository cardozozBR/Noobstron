<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->char('country_code', 2)
                ->default('BR')
                ->after('status');

            $table->string('locale', 16)
                ->default('pt-BR')
                ->after('country_code');

            $table->string('timezone', 64)
                ->default('America/Fortaleza')
                ->after('locale');

            $table->char('currency', 3)
                ->default('BRL')
                ->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'country_code',
                'locale',
                'timezone',
                'currency',
            ]);
        });
    }
};