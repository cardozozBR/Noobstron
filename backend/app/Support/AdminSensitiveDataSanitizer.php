<?php

namespace App\Support;

class AdminSensitiveDataSanitizer
{
    public static function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $patterns = [
            '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i',
            '/\b(sk|pk)_(live|test)_[A-Za-z0-9]+/i',
            '/\b(api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|secret|password)\s*[:=]\s*[^\s,;]+/i',
            '/Authorization\s*:\s*[^\r\n]+/i',
        ];

        $sanitized = preg_replace(
            $patterns,
            '[REDACTED]',
            $value
        );

        if (! is_string($sanitized)) {
            return 'Erro operacional indisponível.';
        }

        return mb_substr(
            $sanitized,
            0,
            500
        );
    }
}