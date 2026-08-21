<?php

namespace App\Services;

use App\Enums\ImportTarget;

class ImportRowNormalizer
{
    public function normalize(
        array $row,
        ImportTarget $target
    ): array {
        $normalized = [];

        foreach ($row as $field => $value) {
            $value = is_string($value)
                ? trim($value)
                : $value;

            if ($value === '') {
                $value = null;
            }

            $normalized[$field] = $value;
        }

        if (
            array_key_exists(
                'email',
                $normalized
            )
            && $normalized['email'] !== null
        ) {
            $normalized['email'] =
                mb_strtolower(
                    $normalized['email']
                );
        }

        if (
            array_key_exists(
                'phone',
                $normalized
            )
            && $normalized['phone'] !== null
        ) {
            $digits = preg_replace(
                '/\D+/',
                '',
                $normalized['phone']
            );

            $normalized['phone'] =
                $digits !== ''
                    ? $digits
                    : null;
        }

        if (
            array_key_exists(
                'tags',
                $normalized
            )
            && $normalized['tags'] !== null
        ) {
            $tags = preg_split(
                '/[,;|]+/',
                $normalized['tags']
            );

            $tags = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn ($tag) =>
                                trim(
                                    (string) $tag
                                ),
                            $tags
                        ),
                        static fn ($tag) =>
                            $tag !== ''
                    )
                )
            );

            $normalized['tags'] = $tags;
        }

        if (
            $target === ImportTarget::LEADS
        ) {
            if (
                array_key_exists(
                    'status',
                    $normalized
                )
                && $normalized['status'] !== null
            ) {
                $normalized['status'] =
                    mb_strtolower(
                        $normalized['status']
                    );
            }

            if (
                array_key_exists(
                    'source',
                    $normalized
                )
                && $normalized['source'] !== null
            ) {
                $normalized['source'] =
                    mb_strtolower(
                        $normalized['source']
                    );
            }
        }

        if (
            $target === ImportTarget::CUSTOMERS
            && array_key_exists(
                'type',
                $normalized
            )
            && $normalized['type'] !== null
        ) {
            $type = mb_strtolower(
                $normalized['type']
            );

            $normalized['type'] = match ($type) {
                'pf',
                'individual',
                'pessoa_fisica',
                'pessoa física' =>
                    'individual',

                'pj',
                'company',
                'empresa',
                'pessoa_juridica',
                'pessoa jurídica' =>
                    'company',

                default => $type,
            };
        }

        if (
            array_key_exists(
                'tax_country_code',
                $normalized
            )
            && $normalized['tax_country_code'] !== null
        ) {
            $normalized['tax_country_code'] =
                strtoupper(
                    $normalized['tax_country_code']
                );
        }

        if (
            array_key_exists(
                'tax_identifier_type',
                $normalized
            )
            && $normalized['tax_identifier_type'] !== null
        ) {
            $normalized['tax_identifier_type'] =
                strtoupper(
                    $normalized['tax_identifier_type']
                );
        }

        if (
            array_key_exists(
                'tax_identifier',
                $normalized
            )
            && $normalized['tax_identifier'] !== null
        ) {
            $normalized['tax_identifier'] =
                preg_replace(
                    '/\D+/',
                    '',
                    $normalized['tax_identifier']
                );
        }

        return $normalized;
    }
}
