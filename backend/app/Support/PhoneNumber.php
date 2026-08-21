<?php

namespace App\Support;

use InvalidArgumentException;

final class PhoneNumber
{
    private const MIN_NATIONAL_DIGITS = 4;
    private const MAX_E164_DIGITS = 15;

    private string $nationalNumber;

    public function __construct(
        private readonly Country $country,
        string $nationalNumber
    ) {
        $nationalNumber = trim($nationalNumber);

        if (!preg_match('/^\d+$/', $nationalNumber)) {
            throw new InvalidArgumentException(
                'Phone number must contain digits only.'
            );
        }

        if (strlen($nationalNumber) < self::MIN_NATIONAL_DIGITS) {
            throw new InvalidArgumentException(
                'Phone number is too short.'
            );
        }

        $internationalDigits =
            $country->callingCode() . $nationalNumber;

        if (strlen($internationalDigits) > self::MAX_E164_DIGITS) {
            throw new InvalidArgumentException(
                'Phone number exceeds the E.164 maximum length.'
            );
        }

        $this->nationalNumber = $nationalNumber;
    }

    public static function fromNational(
        Country|string $country,
        string $nationalNumber
    ): self {
        if (is_string($country)) {
            $country = Country::from($country);
        }

        return new self($country, $nationalNumber);
    }

    public function country(): Country
    {
        return $this->country;
    }

    public function nationalNumber(): string
    {
        return $this->nationalNumber;
    }

    public function international(): string
    {
        return '+'
            . $this->country->callingCode()
            . $this->nationalNumber;
    }

    public function equals(self $other): bool
    {
        return $this->international() === $other->international();
    }
}