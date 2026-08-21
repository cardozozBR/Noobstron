<?php

namespace Tests\Unit;

use App\Enums\ImportTarget;
use App\Services\ImportRowValidator;
use Tests\TestCase;

class ImportRowValidatorTest extends TestCase
{
    public function test_valid_lead_row_passes(): void
    {
        $result = app(
            ImportRowValidator::class
        )->validate(
            [
                'name' => 'Maria',
                'email' =>
                    'maria@example.com',
                'phone' =>
                    '85999999999',
                'status' => 'qualified',
                'source' => 'website',
                'tags' => [
                    'vip',
                ],
            ],
            ImportTarget::LEADS
        );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            [],
            $result['errors']
        );
    }

    public function test_lead_name_is_required(): void
    {
        $result = app(
            ImportRowValidator::class
        )->validate(
            [
                'email' =>
                    'maria@example.com',
            ],
            ImportTarget::LEADS
        );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertArrayHasKey(
            'name',
            $result['errors']
        );
    }

    public function test_invalid_lead_status_is_rejected(): void
    {
        $result = app(
            ImportRowValidator::class
        )->validate(
            [
                'name' => 'Maria',
                'status' => 'invalid',
            ],
            ImportTarget::LEADS
        );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertArrayHasKey(
            'status',
            $result['errors']
        );
    }

    public function test_invalid_email_is_rejected(): void
    {
        $result = app(
            ImportRowValidator::class
        )->validate(
            [
                'name' => 'Maria',
                'email' => 'invalid',
            ],
            ImportTarget::LEADS
        );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertArrayHasKey(
            'email',
            $result['errors']
        );
    }

    public function test_customer_type_is_required_and_valid(): void
    {
        $missing = app(
            ImportRowValidator::class
        )->validate(
            [
                'name' => 'Cliente',
            ],
            ImportTarget::CUSTOMERS
        );

        $this->assertFalse(
            $missing['valid']
        );

        $this->assertArrayHasKey(
            'type',
            $missing['errors']
        );

        $invalid = app(
            ImportRowValidator::class
        )->validate(
            [
                'name' => 'Cliente',
                'type' => 'other',
            ],
            ImportTarget::CUSTOMERS
        );

        $this->assertFalse(
            $invalid['valid']
        );

        $this->assertArrayHasKey(
            'type',
            $invalid['errors']
        );
    }
}
