<?php

namespace App\Support;

use InvalidArgumentException;

final class BrazilTaxIdentifier extends TaxIdentifier
{
    public const CPF = 'CPF';
    public const CNPJ = 'CNPJ';

    public static function cpf(string $value): self
    {
        $digits = self::digitsOnly($value);

        if (!self::isValidCpf($digits)) {
            throw new InvalidArgumentException(
                'Invalid CPF.'
            );
        }

        return new self(
            Country::from('BR'),
            self::CPF,
            $digits
        );
    }

    public static function cnpj(string $value): self
    {
        $digits = self::digitsOnly($value);

        if (!self::isValidCnpj($digits)) {
            throw new InvalidArgumentException(
                'Invalid CNPJ.'
            );
        }

        return new self(
            Country::from('BR'),
            self::CNPJ,
            $digits
        );
    }

    private static function digitsOnly(
        string $value
    ): string {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private static function isValidCpf(
        string $cpf
    ): bool {
        if (
            strlen($cpf) !== 11
            || preg_match('/^(\d)\1{10}$/', $cpf)
        ) {
            return false;
        }

        for ($digit = 9; $digit < 11; $digit++) {
            $sum = 0;

            for ($i = 0; $i < $digit; $i++) {
                $sum += ((int) $cpf[$i])
                    * (($digit + 1) - $i);
            }

            $check = (10 * $sum) % 11;

            if ($check === 10) {
                $check = 0;
            }

            if ($check !== (int) $cpf[$digit]) {
                return false;
            }
        }

        return true;
    }

    private static function isValidCnpj(
        string $cnpj
    ): bool {
        if (
            strlen($cnpj) !== 14
            || preg_match('/^(\d)\1{13}$/', $cnpj)
        ) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $digit1 = self::calculateCnpjDigit(
            substr($cnpj, 0, 12),
            $weights1
        );

        if ($digit1 !== (int) $cnpj[12]) {
            return false;
        }

        $digit2 = self::calculateCnpjDigit(
            substr($cnpj, 0, 13),
            $weights2
        );

        return $digit2 === (int) $cnpj[13];
    }

    private static function calculateCnpjDigit(
        string $base,
        array $weights
    ): int {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += ((int) $base[$index]) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2
            ? 0
            : 11 - $remainder;
    }
}