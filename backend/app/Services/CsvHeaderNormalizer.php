<?php

namespace App\Services;

use RuntimeException;

class CsvHeaderNormalizer
{
    private const ALIASES = [
        'nome' => 'name',
        'name' => 'name',

        'email' => 'email',
        'e_mail' => 'email',

        'telefone' => 'phone',
        'phone' => 'phone',
        'celular' => 'phone',

        'status' => 'status',

        'origem' => 'source',
        'source' => 'source',

        'tags' => 'tags',
        'etiquetas' => 'tags',

        'observacoes' => 'notes',
        'observacao' => 'notes',
        'notes' => 'notes',

        'tipo' => 'type',
        'type' => 'type',

        'razao_social' => 'legal_name',
        'legal_name' => 'legal_name',

        'pais_documento' => 'tax_country_code',
        'tax_country_code' => 'tax_country_code',

        'tipo_documento' => 'tax_identifier_type',
        'tax_identifier_type' => 'tax_identifier_type',

        'documento' => 'tax_identifier',
        'cpf' => 'tax_identifier',
        'cnpj' => 'tax_identifier',
        'tax_identifier' => 'tax_identifier',
    ];

    public function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value);

        $transliterated = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $value
        );

        if ($transliterated !== false) {
            $value = $transliterated;
        }

        $value = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            $value
        );

        $value = trim(
            (string) $value,
            '_'
        );

        return self::ALIASES[$value]
            ?? $value;
    }

    public function normalizeHeader(array $header): array
    {
        $normalized = array_map(
            fn ($value) =>
                $this->normalize(
                    (string) $value
                ),
            $header
        );

        if (in_array('', $normalized, true)) {
            throw new RuntimeException(
                'CSV header contains an empty column.'
            );
        }

        $counts = array_count_values(
            $normalized
        );

        $duplicates = array_keys(
            array_filter(
                $counts,
                static fn ($count) =>
                    $count > 1
            )
        );

        if ($duplicates !== []) {
            throw new RuntimeException(
                'CSV header contains duplicate columns: '
                . implode(', ', $duplicates)
                . '.'
            );
        }

        return $normalized;
    }
}
