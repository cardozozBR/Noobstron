<?php

namespace App\Services;

use App\Enums\ImportTarget;
use RuntimeException;

class ImportPreviewService
{
    public function __construct(
        private readonly CsvImportParser $parser,
        private readonly CsvImportMapping $mapping,
        private readonly CsvImportRowReader $rows,
        private readonly ImportRowNormalizer $normalizer,
        private readonly ImportRowValidator $validator
    ) {
    }

    public function preview(
        string $path,
        ImportTarget $target,
        string $delimiter = ',',
        int $limit = 50
    ): array {
        if ($limit < 1) {
            throw new RuntimeException(
                'Preview limit must be positive.'
            );
        }

        $inspection = $this->parser
            ->inspect(
                $path,
                $delimiter
            );

        $mapping = $this->mapping
            ->build(
                $inspection['header'],
                $target
            );

        $rows = $this->rows
            ->rows(
                $path,
                $mapping['mapping'],
                $inspection['delimiter']
            );

        $validRows = [];
        $invalidRows = [];

        foreach ($rows as $row) {
            $normalized =
                $this->normalizer
                    ->normalize(
                        $row['data'],
                        $target
                    );

            $validation =
                $this->validator
                    ->validate(
                        $normalized,
                        $target
                    );

            $item = [
                'line' => $row['line'],
                'data' => $validation['data'],
                'errors' => $validation['errors'],
            ];

            if ($validation['valid']) {
                $validRows[] = $item;
            } else {
                $invalidRows[] = $item;
            }
        }

        return [
            'target' => $target->value,
            'header' => $mapping['header'],
            'mapping' => $mapping['mapping'],
            'ignored' => $mapping['ignored'],
            'row_count' =>
                count($rows),
            'valid_count' =>
                count($validRows),
            'invalid_count' =>
                count($invalidRows),
            'valid_rows' =>
                array_slice(
                    $validRows,
                    0,
                    $limit
                ),
            'invalid_rows' =>
                array_slice(
                    $invalidRows,
                    0,
                    $limit
                ),
            'preview_limit' => $limit,
        ];
    }
}
