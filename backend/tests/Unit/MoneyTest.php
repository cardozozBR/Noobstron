<?php

namespace Tests\Unit;

use App\Support\Currency;
use App\Support\Money;
use App\Support\MoneyFormatter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_supported_currencies_follow_expected_minor_units(): void
    {
        $this->assertSame(2, Currency::minorUnit('BRL'));
        $this->assertSame(2, Currency::minorUnit('USD'));
        $this->assertSame(2, Currency::minorUnit('EUR'));
        $this->assertSame(0, Currency::minorUnit('JPY'));
        $this->assertSame(2, Currency::minorUnit('CNY'));
    }

    public function test_currency_is_normalized(): void
    {
        $this->assertSame(
            'JPY',
            Currency::normalize(' jpy ')
        );
    }

    public function test_unsupported_currency_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Currency::minorUnit('XXX');
    }

    public function test_money_stores_value_as_integer_minor_units(): void
    {
        $money = Money::fromMinor(1050, 'BRL');

        $this->assertSame(1050, $money->minor);
        $this->assertSame('BRL', $money->currency);
        $this->assertSame(2, $money->decimalPlaces());
        $this->assertSame(100, $money->factor());
    }

    public function test_decimal_string_is_converted_to_minor_units(): void
    {
        $this->assertSame(
            1050,
            Money::fromDecimal('10.50', 'BRL')->minor
        );

        $this->assertSame(
            1999,
            Money::fromDecimal('19.99', 'USD')->minor
        );
    }

    public function test_decimal_conversion_rounds_half_up(): void
    {
        $this->assertSame(
            1001,
            Money::fromDecimal('10.005', 'BRL')->minor
        );

        $this->assertSame(
            1000,
            Money::fromDecimal('10.004', 'BRL')->minor
        );
    }

    public function test_negative_decimal_rounding_is_symmetric(): void
    {
        $this->assertSame(
            -1001,
            Money::fromDecimal('-10.005', 'BRL')->minor
        );

        $this->assertSame(
            -1000,
            Money::fromDecimal('-10.004', 'BRL')->minor
        );
    }

    public function test_jpy_rounds_to_whole_minor_units(): void
    {
        $this->assertSame(
            1051,
            Money::fromDecimal('1050.5', 'JPY')->minor
        );

        $this->assertSame(
            1050,
            Money::fromDecimal('1050.4', 'JPY')->minor
        );
    }

    public function test_invalid_decimal_value_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('10,50', 'BRL');
    }

    public function test_money_comparison_includes_currency(): void
    {
        $brl = Money::fromMinor(1000, 'BRL');
        $same = Money::fromMinor(1000, 'BRL');
        $usd = Money::fromMinor(1000, 'USD');

        $this->assertTrue($brl->equals($same));
        $this->assertFalse($brl->equals($usd));
    }

    public function test_money_sign_helpers(): void
    {
        $this->assertTrue(
            Money::fromMinor(100, 'BRL')->isPositive()
        );

        $this->assertTrue(
            Money::fromMinor(-100, 'BRL')->isNegative()
        );

        $this->assertTrue(
            Money::zero('BRL')->isZero()
        );
    }

    public function test_money_is_formatted_by_locale_and_currency(): void
    {
        $brl = Money::fromMinor(105050, 'BRL');
        $usd = Money::fromMinor(105050, 'USD');
        $eur = Money::fromMinor(105050, 'EUR');
        $jpy = Money::fromMinor(1050, 'JPY');
        $cny = Money::fromMinor(105050, 'CNY');

        $this->assertStringContainsString(
            '1.050,50',
            MoneyFormatter::format($brl, 'pt-BR')
        );

        $this->assertStringContainsString(
            '1,050.50',
            MoneyFormatter::format($usd, 'en-US')
        );

        $this->assertStringContainsString(
            '1.050,50',
            MoneyFormatter::format($eur, 'es-ES')
        );

        $this->assertStringContainsString(
            '1,050',
            MoneyFormatter::format($jpy, 'ja-JP')
        );

        $this->assertStringContainsString(
            '1,050.50',
            MoneyFormatter::format($cny, 'zh-CN')
        );
    }

    public function test_locale_and_currency_are_independent(): void
    {
        $usd = Money::fromMinor(105050, 'USD');

        $formatted = MoneyFormatter::format(
            $usd,
            'pt-BR'
        );

        $this->assertStringContainsString(
            '1.050,50',
            $formatted
        );
    }
    public function test_decimal_overflow_is_rejected(): void
    {
        $this->expectException(\OverflowException::class);

        Money::fromDecimal(
            '999999999999999999999999999999.99',
            'BRL'
        );
    }

    public function test_rounding_that_causes_overflow_is_rejected(): void
    {
        $this->expectException(\OverflowException::class);

        Money::fromDecimal(
            '92233720368547758.075',
            'BRL'
        );
    }

    public function test_zero_with_many_leading_zeroes_is_safe(): void
    {
        $money = Money::fromDecimal(
            '0000000000000000000000.00',
            'BRL'
        );

        $this->assertSame(0, $money->minor);
        $this->assertTrue($money->isZero());
    }

    public function test_decimal_rounding_carries_between_digits(): void
    {
        $this->assertSame(
            1000,
            Money::fromDecimal('9.999', 'BRL')->minor
        );

        $this->assertSame(
            100,
            Money::fromDecimal('99.5', 'JPY')->minor
        );
    }
}