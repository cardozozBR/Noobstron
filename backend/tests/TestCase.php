<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $appEnv = $_ENV['APP_ENV']
            ?? $_SERVER['APP_ENV']
            ?? getenv('APP_ENV');

        $connection = $_ENV['DB_CONNECTION']
            ?? $_SERVER['DB_CONNECTION']
            ?? getenv('DB_CONNECTION');

        $database = $_ENV['DB_DATABASE']
            ?? $_SERVER['DB_DATABASE']
            ?? getenv('DB_DATABASE');

        if (
            $appEnv !== 'testing'
            || $connection !== 'sqlite'
            || $database !== ':memory:'
        ) {
            throw new RuntimeException(
                'Testes bloqueados antes do bootstrap: APP_ENV=testing, DB_CONNECTION=sqlite e DB_DATABASE=:memory: são obrigatórios.'
            );
        }

        parent::setUp();

        $resolvedConnection = config(
            'database.default'
        );

        $resolvedDatabase = config(
            'database.connections.'
                . $resolvedConnection
                . '.database'
        );

        $resolvedDriver = DB::connection()
            ->getDriverName();

        if (
            $resolvedConnection !== 'sqlite'
            || $resolvedDatabase !== ':memory:'
            || $resolvedDriver !== 'sqlite'
        ) {
            throw new RuntimeException(
                'Testes bloqueados após o bootstrap: '
                . 'Laravel deve estar usando sqlite :memory:. '
                . 'Connection='
                . (string) $resolvedConnection
                . '; database='
                . (string) $resolvedDatabase
                . '; driver='
                . (string) $resolvedDriver
                . '.'
            );
        }

        /*
         * Legacy feature tests were written against the Portuguese
         * public experience before browser-language negotiation existed.
         * Pin their default browser language explicitly. Individual i18n
         * tests can still override this header with withHeader().
         */
        $this->withHeader(
            'Accept-Language',
            'pt-BR,pt;q=0.9'
        );
    }
}