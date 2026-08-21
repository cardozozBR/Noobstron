<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('trial_started_at')
                ->nullable()
                ->after('status');

            $table->timestamp('trial_ends_at')
                ->nullable()
                ->after('trial_started_at');

            $table->index(
                'trial_ends_at',
                'tenants_trial_ends_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(
                'tenants_trial_ends_at_index'
            );

            $table->dropColumn([
                'trial_started_at',
                'trial_ends_at',
            ]);
        });
    }
};