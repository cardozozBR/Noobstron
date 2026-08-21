<?php

namespace Tests\Unit;

use App\Services\CsvImportRowReader;
use Tests\TestCase;

class CsvImportRowReaderTest extends TestCase
{
    private function file(
        string $content
    ): string {
        $path = tempnam(
            sys_get_temp_dir(),
            'csv-row-'
        );

        file_put_contents(
            $path,
            $content
        );

        return $path;
    }

    public function test_rows_are_associated_with_internal_fields(): void
    {
        $path = $this->file(
            "Nome,E-mail,Extra\n"
            . "Maria,maria@example.com,x\n"
            . "Joao,joao@example.com,y\n"
        );

        try {
            $rows = app(
                CsvImportRowReader::class
            )->rows(
                $path,
                [
                    0 => 'name',
                    1 => 'email',
                ]
            );

            $this->assertCount(
                2,
                $rows
            );

            $this->assertSame(
                2,
                $rows[0]['line']
            );

            $this->assertSame(
                [
                    'name' => 'Maria',
                    'email' =>
                        'maria@example.com',
                ],
                $rows[0]['data']
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_blank_rows_are_ignored(): void
    {
        $path = $this->file(
            "name,email\n"
            . "Maria,maria@example.com\n"
            . ",\n"
            . "Joao,joao@example.com\n"
        );

        try {
            $rows = app(
                CsvImportRowReader::class
            )->rows(
                $path,
                [
                    0 => 'name',
                    1 => 'email',
                ]
            );

            $this->assertCount(
                2,
                $rows
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_semicolon_rows_are_supported(): void
    {
        $path = $this->file(
            "name;email\n"
            . "Maria;maria@example.com\n"
        );

        try {
            $rows = app(
                CsvImportRowReader::class
            )->rows(
                $path,
                [
                    0 => 'name',
                    1 => 'email',
                ],
                ';'
            );

            $this->assertSame(
                'Maria',
                $rows[0]['data']['name']
            );
        } finally {
            @unlink($path);
        }
    }
}
