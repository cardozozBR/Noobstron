<?php

namespace Tests\Unit;

use App\Services\CsvImportParser;
use RuntimeException;
use Tests\TestCase;

class CsvImportParserTest extends TestCase
{
    private function file(
        string $content
    ): string {
        $path = tempnam(
            sys_get_temp_dir(),
            'csv-import-'
        );

        file_put_contents(
            $path,
            $content
        );

        return $path;
    }

    public function test_csv_header_and_rows_can_be_inspected(): void
    {
        $path = $this->file(
            "name,email\n"
            . "Maria,maria@example.com\n"
            . "Joao,joao@example.com\n"
        );

        try {
            $result = app(
                CsvImportParser::class
            )->inspect(
                $path
            );

            $this->assertSame(
                [
                    'name',
                    'email',
                ],
                $result['header']
            );

            $this->assertSame(
                2,
                $result['row_count']
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_semicolon_delimiter_is_supported(): void
    {
        $path = $this->file(
            "name;email\n"
            . "Maria;maria@example.com\n"
        );

        try {
            $result = app(
                CsvImportParser::class
            )->inspect(
                $path,
                ';'
            );

            $this->assertSame(
                [
                    'name',
                    'email',
                ],
                $result['header']
            );

            $this->assertSame(
                1,
                $result['row_count']
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_empty_csv_is_rejected(): void
    {
        $path = $this->file('');

        try {
            $this->expectException(
                RuntimeException::class
            );

            app(
                CsvImportParser::class
            )->inspect(
                $path
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_invalid_delimiter_is_rejected(): void
    {
        $path = $this->file(
            "name,email\nMaria,maria@example.com\n"
        );

        try {
            $this->expectException(
                RuntimeException::class
            );

            app(
                CsvImportParser::class
            )->inspect(
                $path,
                '|'
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_missing_file_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            CsvImportParser::class
        )->inspect(
            sys_get_temp_dir()
                . '/missing-import.csv'
        );
    }
}
