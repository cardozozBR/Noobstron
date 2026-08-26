<?php

namespace Tests\Unit;

use App\Support\AdminSensitiveDataSanitizer;
use PHPUnit\Framework\TestCase;

class AdminSensitiveDataSanitizerTest extends TestCase
{
    public function test_it_redacts_sensitive_values(): void
    {
        $cases = [
            'Authorization: Bearer abc123.secret'
                => '[REDACTED]',

            'Bearer abc123.secret'
                => '[REDACTED]',

            'Stripe error sk_live_ABC123'
                => 'Stripe error [REDACTED]',

            'api_key=secret123'
                => '[REDACTED]',

            'access_token=mytoken'
                => '[REDACTED]',

            'refresh_token=myrefresh'
                => '[REDACTED]',

            'client_secret=mysecret'
                => '[REDACTED]',

            'password=minhasenha'
                => '[REDACTED]',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame(
                $expected,
                AdminSensitiveDataSanitizer::sanitize($input),
                $input
            );
        }
    }

    public function test_it_preserves_safe_operational_error(): void
    {
        $this->assertSame(
            'Connection timed out.',
            AdminSensitiveDataSanitizer::sanitize(
                'Connection timed out.'
            )
        );
    }

    public function test_it_handles_empty_values(): void
    {
        $this->assertNull(
            AdminSensitiveDataSanitizer::sanitize(null)
        );

        $this->assertNull(
            AdminSensitiveDataSanitizer::sanitize('')
        );

        $this->assertNull(
            AdminSensitiveDataSanitizer::sanitize('   ')
        );
    }

    public function test_it_limits_admin_output_length(): void
    {
        $result = AdminSensitiveDataSanitizer::sanitize(
            str_repeat('A', 1000)
        );

        $this->assertNotNull($result);
        $this->assertLessThanOrEqual(
            500,
            mb_strlen($result)
        );
    }
}