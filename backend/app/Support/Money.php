<?php

namespace App\Support;

use InvalidArgumentException;
use OverflowException;

final readonly class Money
{
    public function __construct(
        public int $minor,
        public string $currency,
    ) {
        $normalized = Currency::normalize($currency);

        if ($normalized !== $currency) {
            throw new InvalidArgumentException(
                'Money currency must be normalized.'
            );
        }
    }

    public static function fromMinor(
        int $minor,
        string $currency
    ): self {
        return new self(
            $minor,
            Currency::normalize($currency)
        );
    }

    public static function fromDecimal(
        string $amount,
        string $currency
    ): self {
        $currency = Currency::normalize($currency);
        $scale = Currency::minorUnit($currency);

        $amount = trim($amount);

        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) {
            throw new InvalidArgumentException(
                "Invalid decimal monetary value: {$amount}"
            );
        }

        $negative = str_starts_with($amount, '-');

        if ($negative) {
            $amount = substr($amount, 1);
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $whole = ltrim($whole, '0');

        if ($whole === '') {
            $whole = '0';
        }

        $fraction = str_pad(
            $fraction,
            $scale + 1,
            '0'
        );

        if ($scale === 0) {
            $minorDigits = $whole;
            $roundDigit = (int) ($fraction[0] ?? '0');
        } else {
            $keptFraction = substr(
                $fraction,
                0,
                $scale
            );

            $minorDigits = ltrim(
                $whole . $keptFraction,
                '0'
            );

            if ($minorDigits === '') {
                $minorDigits = '0';
            }

            $roundDigit = (int) (
                $fraction[$scale] ?? '0'
            );
        }

        if ($roundDigit >= 5) {
            $minorDigits = self::incrementDigits(
                $minorDigits
            );
        }

        self::assertFitsInteger($minorDigits);

        $minor = (int) $minorDigits;

        if ($negative && $minor !== 0) {
            $minor = -$minor;
        }

        return self::fromMinor(
            $minor,
            $currency
        );
    }

    public static function zero(string $currency): self
    {
        return self::fromMinor(0, $currency);
    }

    public function decimalPlaces(): int
    {
        return Currency::minorUnit($this->currency);
    }

    public function factor(): int
    {
        return Currency::factor($this->currency);
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function isPositive(): bool
    {
        return $this->minor > 0;
    }

    public function isNegative(): bool
    {
        return $this->minor < 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency
            && $this->minor === $other->minor;
    }

    private static function assertFitsInteger(
        string $digits
    ): void {
        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return;
        }

        $max = (string) PHP_INT_MAX;

        if (
            strlen($digits) > strlen($max)
            || (
                strlen($digits) === strlen($max)
                && strcmp($digits, $max) > 0
            )
        ) {
            throw new OverflowException(
                'Monetary value exceeds supported integer range.'
            );
        }
    }

    private static function incrementDigits(
        string $digits
    ): string {
        $characters = str_split($digits);

        for (
            $index = count($characters) - 1;
            $index >= 0;
            $index--
        ) {
            if ($characters[$index] !== '9') {
                $characters[$index] = (string) (
                    ((int) $characters[$index]) + 1
                );

                return implode('', $characters);
            }

            $characters[$index] = '0';
        }

        return '1' . implode('', $characters);
    }
}