<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class InternationalAddress
{
    public Country $country;

    public string $line1;

    public string $city;

    public ?string $region;

    public ?string $postalCode;

    public ?string $line2;

    public function __construct(
        Country $country,
        string $line1,
        string $city,
        ?string $region = null,
        ?string $postalCode = null,
        ?string $line2 = null,
    ) {
        $line1 = trim($line1);
        $city = trim($city);
        $region = self::normalizeNullable($region);
        $postalCode = self::normalizeNullable($postalCode);
        $line2 = self::normalizeNullable($line2);

        if ($line1 === '') {
            throw new InvalidArgumentException(
                'Address line1 is required.'
            );
        }

        if ($city === '') {
            throw new InvalidArgumentException(
                'Address city is required.'
            );
        }

        $this->country = $country;
        $this->line1 = $line1;
        $this->city = $city;
        $this->region = $region;
        $this->postalCode = $postalCode;
        $this->line2 = $line2;
    }

    public static function create(
        Country|string $country,
        string $line1,
        string $city,
        ?string $region = null,
        ?string $postalCode = null,
        ?string $line2 = null,
    ): self {
        if (is_string($country)) {
            $country = Country::from($country);
        }

        return new self(
            country: $country,
            line1: $line1,
            city: $city,
            region: $region,
            postalCode: $postalCode,
            line2: $line2,
        );
    }

    public function equals(self $other): bool
    {
        return $this->country->equals($other->country)
            && $this->line1 === $other->line1
            && $this->line2 === $other->line2
            && $this->city === $other->city
            && $this->region === $other->region
            && $this->postalCode === $other->postalCode;
    }

    private static function normalizeNullable(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}