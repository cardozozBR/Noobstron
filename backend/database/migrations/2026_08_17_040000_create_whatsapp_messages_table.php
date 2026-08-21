<?php

use App\Enums\WhatsAppMessageStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'whatsapp_messages',
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
                    'phone',
                    32
                );

                $table->string(
                    'recipient_name'
                )
                    ->nullable();

                $table->text(
                    'body'
                );

                $table->string(
                    'status',
                    32
                )
                    ->default(
                        WhatsAppMessageStatus::PENDING->value
                    );

                $table->string(
                    'direction',
                    16
                )
                    ->default(
                        'outbound'
                    );

                $table->string(
                    'provider',
                    64
                )
                    ->nullable();

                $table->string(
                    'provider_message_id'
                )
                    ->nullable();

                $table->timestampTz(
                    'sent_at'
                )
                    ->nullable();

                $table->timestampTz(
                    'delivered_at'
                )
                    ->nullable();

                $table->timestampTz(
                    'read_at'
                )
                    ->nullable();

                $table->timestampTz(
                    'received_at'
                )
                    ->nullable();

                $table->timestampTz(
                    'failed_at'
                )
                    ->nullable();

                $table->text(
                    'failure_reason'
                )
                    ->nullable();

                $table->timestampsTz();

                $table->index([
                    'tenant_id',
                    'status',
                ]);

                $table->index([
                    'tenant_id',
                    'phone',
                ]);

                $table->index([
                    'tenant_id',
                    'direction',
                ]);

                $table->unique([
                    'tenant_id',
                    'provider',
                    'provider_message_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'whatsapp_messages'
        );
    }
};