<?php

namespace App\Services;

use RuntimeException;
use SplFileObject;

class CsvImportRowReader
{
    public function rows(
        string $path,
        array $mapping,
        string $delimiter = ','
    ): array {
        if (! is_file($path)) {
            throw new RuntimeException(
                'CSV file not found.'
            );
        }

        $file = new SplFileObject(
            $path,
            'r'
        );

        $file->setFlags(
            SplFileObject::READ_CSV
            | SplFileObject::SKIP_EMPTY
            | SplFileObject::DROP_NEW_LINE
        );

        $file->setCsvControl(
            $delimiter
        );

        $rows = [];
        $line = 0;

        foreach ($file as $values) {
            if (
                ! is_array($values)
                || $values === [null]
            ) {
                continue;
            }

            $line++;

            if ($line === 1) {
                continue;
            }

            if (
                count(
                    array_filter(
                        $values,
                        static fn ($value) =>
                            $value !== null
                            && trim(
                                (string) $value
                            ) !== ''
                    )
                ) === 0
            ) {
                continue;
            }

            $row = [];

            foreach ($mapping as $index => $field) {
                $value = $values[$index]
                    ?? null;

                $row[$field] =
                    is_string($value)
                        ? trim($value)
                        : $value;
            }

            $rows[] = [
                'line' => $line,
                'data' => $row,
            ];
        }

        return $rows;
    }
}
