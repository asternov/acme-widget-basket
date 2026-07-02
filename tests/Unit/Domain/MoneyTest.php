<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Basket\Money;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_creates_from_cents(): void
    {
        $this->assertSame(3295, Money::fromCents(3295)->cents());
        $this->assertSame('32.95', (string) Money::fromCents(3295));
    }

    public function test_formats_amounts_under_a_dollar(): void
    {
        $this->assertSame('0.05', (string) Money::fromCents(5));
        $this->assertSame('0.00', (string) Money::zero());
    }

    public function test_adds_and_multiplies(): void
    {
        $money = Money::fromCents(1000)->add(Money::fromCents(295));

        $this->assertSame('12.95', (string) $money);
        $this->assertSame('25.90', (string) $money->multiply(2));
    }

    public function test_halves_without_losing_the_half_cent(): void
    {
        $half = Money::fromCents(3295)->half();

        $this->assertTrue($half->multiply(2)->equals(Money::fromCents(3295)));
    }

    public function test_refuses_to_expose_cents_of_a_sub_cent_amount(): void
    {
        $this->expectException(LogicException::class);

        Money::fromCents(3295)->half()->cents();
    }

    public function test_refuses_to_halve_below_half_cent_precision(): void
    {
        $this->expectException(LogicException::class);

        Money::fromCents(3295)->half()->half();
    }

    public function test_truncates_sub_cent_amounts_down_to_a_whole_cent(): void
    {
        $subtotal = Money::fromCents(6590)->subtract(Money::fromCents(3295)->half());

        $this->assertSame('49.42', (string) $subtotal->truncateToCent());
    }

    public function test_truncating_a_whole_cent_amount_changes_nothing(): void
    {
        $money = Money::fromCents(4295);

        $this->assertTrue($money->truncateToCent()->equals($money));
    }

    public function test_compares_amounts(): void
    {
        $this->assertTrue(Money::fromCents(9000)->isAtLeast(Money::fromCents(9000)));
        $this->assertTrue(Money::fromCents(9001)->isAtLeast(Money::fromCents(9000)));
        $this->assertFalse(Money::fromCents(8999)->isAtLeast(Money::fromCents(9000)));
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(100)->subtract(Money::fromCents(200));
    }
}
