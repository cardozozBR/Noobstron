<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'whatsapp_messages',
            function (Blueprint $table): void {
                $table->foreignId(
                    'whatsapp_template_id'
                )
                    ->nullable()
                    ->after('body')
                    ->constrained(
                        'whatsapp_templates'
                    )
                    ->nullOnDelete();

                $table->json(
                    'template_variables'
                )
                    ->nullable()
                    ->after(
                        'whatsapp_template_id'
                    );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'whatsapp_messages',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'whatsapp_template_id',
                ]);

                $table->dropColumn([
                    'whatsapp_template_id',
                    'template_variables',
                ]);
            }
        );
    }
};