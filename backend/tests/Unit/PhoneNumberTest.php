<?php

namespace Tests\Unit;

use App\Support\Country;
use App\Support\PhoneNumber;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_brazilian_number_has_international_representation(): void
    {
        $phone = PhoneNumber::fromNational(
            'BR',
            '85999998888'
        );

        $this->assertSame(
            '85999998888',
            $phone->nationalNumber()
        );

        $this->assertSame(
            '+5585999998888',
            $phone->international()
        );
    }

    public function test_phone_accepts_country_object(): void
    {
        $phone = PhoneNumber::fromNational(
            Country::from('JP'),
            '9012345678'
        );

        $this->assertSame(
            'JP',
            $phone->country()->code()
        );

        $this->assertSame(
            '+819012345678',
            $phone->international()
        );
    }

    public function test_different_countries_use_their_calling_codes(): void
    {
        $cases = [
            ['US', '2025550100', '+12025550100'],
            ['ES', '612345678', '+34612345678'],
            ['CN', '13800138000', '+8613800138000'],
        ];

        foreach ($cases as [$country, $national, $expected]) {
            $this->assertSame(
                $expected,
                PhoneNumber::fromNational(
                    $country,
                    $national
                )->international()
            );
        }
    }

    public function test_non_digit_national_number_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        PhoneNumber::fromNational(
            'BR',
            '(85) 99999-8888'
        );
    }

    public function test_short_number_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        PhoneNumber::fromNational('BR', '123');
    }

    public function test_e164_overflow_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        PhoneNumber::fromNational(
            'BR',
            '12345678901234'
        );
    }

    public function test_phone_equality_uses_international_representation(): void
    {
        $a = PhoneNumber::fromNational(
            'BR',
            '85999998888'
        );

        $b = PhoneNumber::fromNational(
            'br',
            '85999998888'
        );

        $c = PhoneNumber::fromNational(
            'US',
            '85999998888'
        );

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}