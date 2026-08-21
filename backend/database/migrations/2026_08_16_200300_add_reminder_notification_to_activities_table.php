<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'activities',
            function (Blueprint $table): void {
                $table->timestamp(
                    'reminder_notified_at'
                )
                    ->nullable()
                    ->after('completed_at');

                $table->index([
                    'tenant_id',
                    'status',
                    'due_at',
                    'reminder_notified_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'activities',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'tenant_id',
                    'status',
                    'due_at',
                    'reminder_notified_at',
                ]);

                $table->dropColumn(
                    'reminder_notified_at'
                );
            }
        );
    }
};
