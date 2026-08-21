<?php

namespace Tests\Unit;

use App\Services\CsvHeaderNormalizer;
use RuntimeException;
use Tests\TestCase;

class CsvHeaderNormalizerTest extends TestCase
{
    public function test_header_names_are_normalized(): void
    {
        $service = app(
            CsvHeaderNormalizer::class
        );

        $this->assertSame(
            [
                'name',
                'email',
                'phone',
                'notes',
            ],
            $service->normalizeHeader([
                'Nome',
                'E-mail',
                'Telefone',
                'Observações',
            ])
        );
    }

    public function test_accents_are_removed(): void
    {
        $service = app(
            CsvHeaderNormalizer::class
        );

        $this->assertSame(
            'legal_name',
            $service->normalize(
                'Razão Social'
            )
        );
    }

    public function test_empty_header_column_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            CsvHeaderNormalizer::class
        )->normalizeHeader([
            'name',
            '',
        ]);
    }

    public function test_duplicate_header_is_rejected_after_normalization(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            CsvHeaderNormalizer::class
        )->normalizeHeader([
            'nome',
            'name',
        ]);
    }
}
