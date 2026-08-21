<?php

use App\Enums\ConversationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'conversations',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table->foreignId(
                    'tenant_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'channel',
                    32
                );

                $table->string(
                    'external_address'
                );

                $table->string(
                    'display_name'
                )
                    ->nullable();

                $table->foreignId(
                    'responsible_user_id'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

                $table->foreignId(
                    'lead_id'
                )
                    ->nullable()
                    ->constrained(
                        'leads'
                    )
                    ->nullOnDelete();

                $table->foreignId(
                    'customer_id'
                )
                    ->nullable()
                    ->constrained(
                        'customers'
                    )
                    ->nullOnDelete();

                $table->string(
                    'status',
                    32
                )
                    ->default(
                        ConversationStatus::OPEN->value
                    );

                $table->timestampTz(
                    'last_message_at'
                )
                    ->nullable();

                $table->timestampTz(
                    'closed_at'
                )
                    ->nullable();

                $table->timestampsTz();

                $table->index([
                    'tenant_id',
                    'status',
                    'last_message_at',
                ]);

                $table->index([
                    'tenant_id',
                    'responsible_user_id',
                    'status',
                ]);

                $table->index([
                    'tenant_id',
                    'customer_id',
                ]);

                $table->index([
                    'tenant_id',
                    'lead_id',
                ]);

                $table->unique(
                    [
                        'tenant_id',
                        'channel',
                        'external_address',
                    ],
                    'conversations_tenant_channel_address_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'conversations'
        );
    }
};