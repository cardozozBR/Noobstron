<?php

namespace App\Services;

use App\Enums\ImportTarget;
use RuntimeException;

class CsvImportMapping
{
    public function __construct(
        private readonly CsvHeaderNormalizer $headers
    ) {
    }

    public function build(
        array $header,
        ImportTarget $target
    ): array {
        $normalized = $this->headers
            ->normalizeHeader(
                $header
            );

        $supported = $target
            ->supportedFields();

        $mapping = [];

        foreach ($normalized as $index => $field) {
            if (! in_array(
                $field,
                $supported,
                true
            )) {
                continue;
            }

            $mapping[$index] = $field;
        }

        $mappedFields = array_values(
            $mapping
        );

        $missing = array_values(
            array_diff(
                $target->requiredFields(),
                $mappedFields
            )
        );

        if ($missing !== []) {
            throw new RuntimeException(
                'Required import fields are missing: '
                . implode(', ', $missing)
                . '.'
            );
        }

        return [
            'header' => $normalized,
            'mapping' => $mapping,
            'ignored' => array_values(
                array_filter(
                    $normalized,
                    static fn ($field) =>
                        ! in_array(
                            $field,
                            $supported,
                            true
                        )
                )
            ),
        ];
    }
}
