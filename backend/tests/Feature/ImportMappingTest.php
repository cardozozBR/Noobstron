<?php

namespace Tests\Feature;

use App\Enums\ImportTarget;
use App\Services\CsvHeaderNormalizer;
use App\Services\CsvImportMapping;
use App\Services\CsvImportParser;
use App\Services\CsvImportRowReader;
use Tests\TestCase;

class ImportMappingTest extends TestCase
{
    public function test_csv_can_be_inspected_mapped_and_read(): void
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'csv-mapping-'
        );

        file_put_contents(
            $path,
            "Nome,E-mail,Telefone,Campo Externo\n"
            . "Maria,maria@example.com,85999999999,abc\n"
        );

        try {
            $inspection = app(
                CsvImportParser::class
            )->inspect(
                $path
            );

            $mapping = app(
                CsvImportMapping::class
            )->build(
                $inspection['header'],
                ImportTarget::LEADS
            );

            $rows = app(
                CsvImportRowReader::class
            )->rows(
                $path,
                $mapping['mapping'],
                $inspection['delimiter']
            );

            $this->assertSame(
                [
                    'name',
                    'email',
                    'phone',
                    'campo_externo',
                ],
                $mapping['header']
            );

            $this->assertSame(
                [
                    'campo_externo',
                ],
                $mapping['ignored']
            );

            $this->assertSame(
                [
                    'name' => 'Maria',
                    'email' =>
                        'maria@example.com',
                    'phone' =>
                        '85999999999',
                ],
                $rows[0]['data']
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_normalizer_is_idempotent(): void
    {
        $service = app(
            CsvHeaderNormalizer::class
        );

        $once = $service->normalize(
            'Razão Social'
        );

        $twice = $service->normalize(
            $once
        );

        $this->assertSame(
            $once,
            $twice
        );
    }
}
