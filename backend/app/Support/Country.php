<?php

namespace App\Support;

use InvalidArgumentException;

final class Country
{
    private const CALLING_CODES = [
        'BR' => '55',
        'US' => '1',
        'ES' => '34',
        'JP' => '81',
        'CN' => '86',
    ];

    private string $code;

    public function __construct(string $code)
    {
        $code = strtoupper(trim($code));

        if (!array_key_exists($code, self::CALLING_CODES)) {
            throw new InvalidArgumentException(
                "Unsupported country code: {$code}"
            );
        }

        $this->code = $code;
    }

    public static function from(string $code): self
    {
        return new self($code);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function callingCode(): string
    {
        return self::CALLING_CODES[$this->code];
    }

    public function callingCodeWithPlus(): string
    {
        return '+' . $this->callingCode();
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
