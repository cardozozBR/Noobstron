<?php

namespace App\Support;

final readonly class AutomationActionResult
{
    public function __construct(
        public bool $successful,
        public array $data = [],
        public ?string $error = null,
    ) {
    }

    public static function success(
        array $data = []
    ): self {
        return new self(
            successful: true,
            data: $data,
        );
    }

    public static function failure(
        string $error,
        array $data = []
    ): self {
        return new self(
            successful: false,
            data: $data,
            error: trim($error),
        );
    }
}