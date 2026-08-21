<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Os testes usam SQLite em memória e não possuem timestamps
         * legados de development/production para normalizar.
         */
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->convertColumns('tenants', [
            'created_at',
            'updated_at',
        ]);

        $this->convertColumns('users', [
            'email_verified_at',
            'created_at',
            'updated_at',
        ]);

        $this->convertColumns('permissions', [
            'created_at',
            'updated_at',
        ]);

        $this->convertColumns('permission_user', [
            'created_at',
            'updated_at',
        ]);

        $this->convertColumns('audit_logs', [
            'created_at',
            'updated_at',
        ]);

        $this->convertColumns('password_reset_tokens', [
            'created_at',
        ]);
    }

    public function down(): void
    {
        /*
         * Esta normalização é intencionalmente irreversível.
         *
         * Depois da transição, novos timestamps já possuem semântica UTC.
         * Converter todos novamente como America/Fortaleza corromperia
         * os registros criados após esta migration.
         */
        throw new RuntimeException(
            'UTC timestamp normalization is intentionally irreversible.'
        );
    }

    private function convertColumns(
        string $table,
        array $columns
    ): void {
        foreach ($columns as $column) {
            DB::statement(
                sprintf(
                    <<<'SQL'
                    UPDATE "%s"
                    SET "%s" = (
                        ("%s" AT TIME ZONE 'America/Fortaleza')
                        AT TIME ZONE 'UTC'
                    )
                    WHERE "%s" IS NOT NULL
                    SQL,
                    $table,
                    $column,
                    $column,
                    $column,
                )
            );
        }
    }
};