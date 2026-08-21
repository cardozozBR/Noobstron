<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type', 30);
            $table->string('name');
            $table->string('legal_name')->nullable();

            $table->string('tax_country_code', 2)->nullable();
            $table->string('tax_identifier_type', 50)->nullable();
            $table->string('tax_identifier', 100)->nullable();

            $table->json('tags')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            /*
             * Necessário para permitir FKs compostas que garantem
             * que registros filhos pertençam ao mesmo tenant.
             */
            $table->unique(
                ['id', 'tenant_id'],
                'customers_id_tenant_unique'
            );

            $table->index(
                ['tenant_id', 'name'],
                'customers_tenant_name_index'
            );

            $table->index(
                ['tenant_id', 'type'],
                'customers_tenant_type_index'
            );

            $table->index(
                ['tenant_id', 'responsible_user_id'],
                'customers_tenant_responsible_index'
            );

            $table->index(
                ['tenant_id', 'created_at'],
                'customers_tenant_created_index'
            );

            $table->index(
                ['tenant_id', 'tax_identifier'],
                'customers_tenant_tax_identifier_index'
            );
        });

        Schema::create(
            'customer_contacts',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->unsignedBigInteger('customer_id');

                $table->string('name');
                $table->string('role')->nullable();

                $table->string('type', 30)
                    ->default('general');

                $table->text('notes')->nullable();

                $table->timestamps();

                /*
                 * Um contato somente pode apontar para customer
                 * do mesmo tenant.
                 */
                $table->foreign(
                    ['customer_id', 'tenant_id'],
                    'customer_contacts_customer_tenant_foreign'
                )
                    ->references(['id', 'tenant_id'])
                    ->on('customers')
                    ->cascadeOnDelete();

                /*
                 * Telefones/e-mails poderão validar simultaneamente
                 * contato + customer + tenant.
                 */
                $table->unique(
                    ['id', 'tenant_id', 'customer_id'],
                    'customer_contacts_identity_unique'
                );

                $table->index(
                    ['tenant_id', 'customer_id'],
                    'customer_contacts_tenant_customer_index'
                );

                $table->index(
                    ['tenant_id', 'name'],
                    'customer_contacts_tenant_name_index'
                );
            }
        );

        Schema::create(
            'customer_phones',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->unsignedBigInteger('customer_id');

                $table->unsignedBigInteger(
                    'customer_contact_id'
                )->nullable();

                $table->string('label')->nullable();
                $table->string('country_code', 2);
                $table->string('national_number', 30);

                $table->boolean('is_primary')
                    ->default(false);

                $table->timestamps();

                $table->foreign(
                    ['customer_id', 'tenant_id'],
                    'customer_phones_customer_tenant_foreign'
                )
                    ->references(['id', 'tenant_id'])
                    ->on('customers')
                    ->cascadeOnDelete();

                /*
                 * Se customer_contact_id estiver definido,
                 * contato, customer e tenant precisam coincidir.
                 *
                 * Com FK MATCH SIMPLE, customer_contact_id NULL
                 * continua permitido.
                 */
                $table->foreign(
                    [
                        'customer_contact_id',
                        'tenant_id',
                        'customer_id',
                    ],
                    'customer_phones_contact_identity_foreign'
                )
                    ->references([
                        'id',
                        'tenant_id',
                        'customer_id',
                    ])
                    ->on('customer_contacts')
                    ->cascadeOnDelete();

                $table->index(
                    ['tenant_id', 'customer_id'],
                    'customer_phones_tenant_customer_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'country_code',
                        'national_number',
                    ],
                    'customer_phones_tenant_number_index'
                );
            }
        );

        Schema::create(
            'customer_emails',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->unsignedBigInteger('customer_id');

                $table->unsignedBigInteger(
                    'customer_contact_id'
                )->nullable();

                $table->string('label')->nullable();
                $table->string('email');

                $table->boolean('is_primary')
                    ->default(false);

                $table->timestamps();

                $table->foreign(
                    ['customer_id', 'tenant_id'],
                    'customer_emails_customer_tenant_foreign'
                )
                    ->references(['id', 'tenant_id'])
                    ->on('customers')
                    ->cascadeOnDelete();

                $table->foreign(
                    [
                        'customer_contact_id',
                        'tenant_id',
                        'customer_id',
                    ],
                    'customer_emails_contact_identity_foreign'
                )
                    ->references([
                        'id',
                        'tenant_id',
                        'customer_id',
                    ])
                    ->on('customer_contacts')
                    ->cascadeOnDelete();

                $table->index(
                    ['tenant_id', 'customer_id'],
                    'customer_emails_tenant_customer_index'
                );

                $table->index(
                    ['tenant_id', 'email'],
                    'customer_emails_tenant_email_index'
                );
            }
        );

        Schema::create(
            'customer_addresses',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->unsignedBigInteger('customer_id');

                $table->string('label')->nullable();
                $table->string('country_code', 2);
                $table->string('line1');
                $table->string('line2')->nullable();
                $table->string('city');
                $table->string('region')->nullable();
                $table->string('postal_code')->nullable();

                $table->boolean('is_primary')
                    ->default(false);

                $table->timestamps();

                $table->foreign(
                    ['customer_id', 'tenant_id'],
                    'customer_addresses_customer_tenant_foreign'
                )
                    ->references(['id', 'tenant_id'])
                    ->on('customers')
                    ->cascadeOnDelete();

                $table->index(
                    ['tenant_id', 'customer_id'],
                    'customer_addresses_tenant_customer_index'
                );

                $table->index(
                    ['tenant_id', 'city'],
                    'customer_addresses_tenant_city_index'
                );
            }
        );

        Schema::create(
            'customer_history',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->unsignedBigInteger('customer_id');

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('event');
                $table->text('description')->nullable();

                $table->nullableMorphs('subject');

                $table->json('metadata')->nullable();

                $table->timestamps();

                $table->foreign(
                    ['customer_id', 'tenant_id'],
                    'customer_history_customer_tenant_foreign'
                )
                    ->references(['id', 'tenant_id'])
                    ->on('customers')
                    ->cascadeOnDelete();

                $table->index(
                    [
                        'tenant_id',
                        'customer_id',
                        'created_at',
                    ],
                    'customer_history_tenant_customer_created_index'
                );

                $table->index(
                    ['tenant_id', 'event'],
                    'customer_history_tenant_event_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_history');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customer_emails');
        Schema::dropIfExists('customer_phones');
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customers');
    }
};
