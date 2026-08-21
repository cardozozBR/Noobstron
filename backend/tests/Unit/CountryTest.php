<?php

namespace Tests\Unit;

use App\Support\Country;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CountryTest extends TestCase
{
    public function test_country_code_is_normalized(): void
    {
        $country = Country::from(' br ');

        $this->assertSame('BR', $country->code());
    }

    public function test_supported_countries_have_calling_codes(): void
    {
        $expected = [
            'BR' => '55',
            'US' => '1',
            'ES' => '34',
            'JP' => '81',
            'CN' => '86',
        ];

        foreach ($expected as $code => $callingCode) {
            $country = Country::from($code);

            $this->assertSame($callingCode, $country->callingCode());
            $this->assertSame('+' . $callingCode, $country->callingCodeWithPlus());
        }
    }

    public function test_unsupported_country_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Country::from('XX');
    }

    public function test_country_equality_uses_iso_code(): void
    {
        $this->assertTrue(
            Country::from('BR')->equals(Country::from('br'))
        );

        $this->assertFalse(
            Country::from('BR')->equals(Country::from('US'))
        );
    }
}
