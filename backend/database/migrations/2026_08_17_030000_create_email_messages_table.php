<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'email_messages',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('to_email');

                $table->string('to_name')
                    ->nullable();

                $table->string('subject');

                $table->text('body');

                $table->string(
                    'status',
                    30
                )->default('pending');

                $table->timestampTz('sent_at')
                    ->nullable();

                $table->timestampTz('failed_at')
                    ->nullable();

                $table->text('failure_reason')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'tenant_id',
                        'status',
                        'created_at',
                    ],
                    'email_messages_tenant_status_created_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'to_email',
                    ],
                    'email_messages_tenant_recipient_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'email_messages'
        );
    }
};
