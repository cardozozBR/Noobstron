<?php

namespace App\Services;

use RuntimeException;
use SplFileObject;

class CsvImportParser
{
    public function inspect(
        string $path,
        string $delimiter = ','
    ): array {
        if (! is_file($path)) {
            throw new RuntimeException(
                'CSV file not found.'
            );
        }

        if (! in_array(
            $delimiter,
            [',', ';', "\t"],
            true
        )) {
            throw new RuntimeException(
                'Unsupported CSV delimiter.'
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

        $header = null;
        $rowCount = 0;

        foreach ($file as $row) {
            if (
                ! is_array($row)
                || $row === [null]
            ) {
                continue;
            }

            $row = array_map(
                static fn ($value) =>
                    is_string($value)
                        ? trim($value)
                        : $value,
                $row
            );

            if ($header === null) {
                $header = $row;
                continue;
            }

            if (
                count(
                    array_filter(
                        $row,
                        static fn ($value) =>
                            $value !== null
                            && $value !== ''
                    )
                ) === 0
            ) {
                continue;
            }

            $rowCount++;
        }

        if ($header === null) {
            throw new RuntimeException(
                'CSV file is empty.'
            );
        }

        $header = array_map(
            static fn ($value) =>
                is_string($value)
                    ? trim($value)
                    : '',
            $header
        );

        if (
            count(
                array_filter(
                    $header,
                    static fn ($value) =>
                        $value !== ''
                )
            ) === 0
        ) {
            throw new RuntimeException(
                'CSV header is empty.'
            );
        }

        return [
            'header' => $header,
            'row_count' => $rowCount,
            'delimiter' => $delimiter,
            'encoding' => 'UTF-8',
        ];
    }
}
