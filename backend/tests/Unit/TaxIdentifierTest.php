<?php

namespace Tests\Unit;

use App\Support\BrazilTaxIdentifier;
use App\Support\TaxIdentifier;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TaxIdentifierTest extends TestCase
{
    public function test_generic_tax_identifier_supports_other_countries(): void
    {
        $tax = TaxIdentifier::create(
            'US',
            'EIN',
            '12-3456789'
        );

        $this->assertSame('US', $tax->country()->code());
        $this->assertSame('EIN', $tax->type());
        $this->assertSame('12-3456789', $tax->value());
    }

    public function test_generic_tax_identifier_requires_type(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        TaxIdentifier::create(
            'ES',
            '',
            'X1234567L'
        );
    }

    public function test_generic_tax_identifier_requires_value(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        TaxIdentifier::create(
            'JP',
            'CORPORATE',
            ''
        );
    }

    public function test_valid_cpf_is_normalized(): void
    {
        $cpf = BrazilTaxIdentifier::cpf(
            '529.982.247-25'
        );

        $this->assertSame(
            'BR',
            $cpf->country()->code()
        );

        $this->assertSame(
            'CPF',
            $cpf->type()
        );

        $this->assertSame(
            '52998224725',
            $cpf->value()
        );
    }

    public function test_invalid_cpf_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        BrazilTaxIdentifier::cpf(
            '111.111.111-11'
        );
    }

    public function test_valid_cnpj_is_normalized(): void
    {
        $cnpj = BrazilTaxIdentifier::cnpj(
            '04.252.011/0001-10'
        );

        $this->assertSame(
            'BR',
            $cnpj->country()->code()
        );

        $this->assertSame(
            'CNPJ',
            $cnpj->type()
        );

        $this->assertSame(
            '04252011000110',
            $cnpj->value()
        );
    }

    public function test_invalid_cnpj_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        BrazilTaxIdentifier::cnpj(
            '11.111.111/1111-11'
        );
    }

    public function test_tax_identifier_equality_uses_country_type_and_value(): void
    {
        $a = BrazilTaxIdentifier::cpf(
            '52998224725'
        );

        $b = BrazilTaxIdentifier::cpf(
            '529.982.247-25'
        );

        $c = TaxIdentifier::create(
            'BR',
            'OTHER',
            '52998224725'
        );

        $this->assertTrue(
            $a->equals($b)
        );

        $this->assertFalse(
            $a->equals($c)
        );
    }
}