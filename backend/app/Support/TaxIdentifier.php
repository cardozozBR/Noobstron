<?php

namespace App\Support;

use InvalidArgumentException;

class TaxIdentifier
{
    public function __construct(
        private readonly Country $country,
        private readonly string $type,
        private readonly string $value,
    ) {
        if (trim($type) === '') {
            throw new InvalidArgumentException(
                'Tax identifier type is required.'
            );
        }

        if (trim($value) === '') {
            throw new InvalidArgumentException(
                'Tax identifier value is required.'
            );
        }
    }

    public static function create(
        Country|string $country,
        string $type,
        string $value
    ): self {
        if (is_string($country)) {
            $country = Country::from($country);
        }

        return new self(
            $country,
            strtoupper(trim($type)),
            trim($value),
        );
    }

    public function country(): Country
    {
        return $this->country;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->country->equals($other->country())
            && $this->type === $other->type()
            && $this->value === $other->value();
    }
}