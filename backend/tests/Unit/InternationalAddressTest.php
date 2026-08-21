<?php

namespace Tests\Unit;

use App\Support\Country;
use App\Support\InternationalAddress;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InternationalAddressTest extends TestCase
{
    public function test_brazilian_address_can_be_created(): void
    {
        $address = InternationalAddress::create(
            country: 'BR',
            line1: 'Rua Exemplo, 100',
            city: 'Fortaleza',
            region: 'CE',
            postalCode: '60123456',
            line2: 'Sala 10',
        );

        $this->assertSame('BR', $address->country->code());
        $this->assertSame('Rua Exemplo, 100', $address->line1);
        $this->assertSame('Fortaleza', $address->city);
        $this->assertSame('CE', $address->region);
        $this->assertSame('60123456', $address->postalCode);
        $this->assertSame('Sala 10', $address->line2);
    }

    public function test_japanese_address_does_not_require_brazilian_structure(): void
    {
        $address = InternationalAddress::create(
            country: Country::from('JP'),
            line1: '1-1 Chiyoda',
            city: 'Tokyo',
            postalCode: '1000001',
        );

        $this->assertSame('JP', $address->country->code());
        $this->assertSame('Tokyo', $address->city);
        $this->assertNull($address->region);
        $this->assertSame('1000001', $address->postalCode);
    }

    public function test_optional_fields_are_normalized(): void
    {
        $address = InternationalAddress::create(
            country: 'US',
            line1: '1 Main Street',
            city: 'New York',
            region: '  ',
            postalCode: null,
            line2: '',
        );

        $this->assertNull($address->region);
        $this->assertNull($address->postalCode);
        $this->assertNull($address->line2);
    }

    public function test_required_values_are_trimmed(): void
    {
        $address = InternationalAddress::create(
            country: 'ES',
            line1: '  Calle Mayor 10  ',
            city: '  Madrid  ',
        );

        $this->assertSame(
            'Calle Mayor 10',
            $address->line1
        );

        $this->assertSame(
            'Madrid',
            $address->city
        );
    }

    public function test_empty_line1_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        InternationalAddress::create(
            country: 'BR',
            line1: '   ',
            city: 'Fortaleza',
        );
    }

    public function test_empty_city_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        InternationalAddress::create(
            country: 'BR',
            line1: 'Rua Exemplo',
            city: '',
        );
    }

    public function test_address_equality_uses_all_fields(): void
    {
        $a = InternationalAddress::create(
            country: 'CN',
            line1: '88 Nanjing Road',
            city: 'Shanghai',
            region: 'Shanghai',
            postalCode: '200001',
        );

        $b = InternationalAddress::create(
            country: 'cn',
            line1: '88 Nanjing Road',
            city: 'Shanghai',
            region: 'Shanghai',
            postalCode: '200001',
        );

        $c = InternationalAddress::create(
            country: 'CN',
            line1: '99 Nanjing Road',
            city: 'Shanghai',
            region: 'Shanghai',
            postalCode: '200001',
        );

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}