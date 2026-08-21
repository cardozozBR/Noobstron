<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'email_messages',
            function (
                Blueprint $table
            ): void {
                $table->foreignId(
                    'conversation_id'
                )
                    ->nullable()
                    ->after(
                        'tenant_id'
                    )
                    ->constrained(
                        'conversations'
                    )
                    ->nullOnDelete();

                $table->index(
                    [
                        'tenant_id',
                        'conversation_id',
                        'created_at',
                    ],
                    'email_messages_tenant_conversation_created_index'
                );
            }
        );

        Schema::table(
            'whatsapp_messages',
            function (
                Blueprint $table
            ): void {
                $table->foreignId(
                    'conversation_id'
                )
                    ->nullable()
                    ->after(
                        'tenant_id'
                    )
                    ->constrained(
                        'conversations'
                    )
                    ->nullOnDelete();

                $table->index(
                    [
                        'tenant_id',
                        'conversation_id',
                        'created_at',
                    ],
                    'whatsapp_messages_tenant_conversation_created_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'whatsapp_messages',
            function (
                Blueprint $table
            ): void {
                $table->dropIndex(
                    'whatsapp_messages_tenant_conversation_created_index'
                );

                $table->dropConstrainedForeignId(
                    'conversation_id'
                );
            }
        );

        Schema::table(
            'email_messages',
            function (
                Blueprint $table
            ): void {
                $table->dropIndex(
                    'email_messages_tenant_conversation_created_index'
                );

                $table->dropConstrainedForeignId(
                    'conversation_id'
                );
            }
        );
    }
};