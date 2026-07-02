<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Basket\Coupons;
use App\Domain\Basket\Money;
use App\Domain\Basket\PercentageCoupon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PercentageCouponTest extends TestCase
{
    public function test_discounts_a_percentage_of_the_goods_total(): void
    {
        $coupon = new PercentageCoupon('WIDGET10', 10);

        $discount = $coupon->discount(Money::fromCents(9827));

        $this->assertSame('9.82', (string) $discount);
    }

    public function test_rejects_percentages_that_make_no_sense(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PercentageCoupon('BROKEN', 0);
    }

    public function test_finds_coupons_by_code(): void
    {
        $coupons = new Coupons([
            new PercentageCoupon('WIDGET10', 10),
            new PercentageCoupon('ACME20', 20),
        ]);

        $this->assertSame(10, $coupons->find('WIDGET10')?->percentOff);
        $this->assertNull($coupons->find('NOPE'));
        $this->assertSame(['WIDGET10', 'ACME20'], $coupons->codes());
    }
}
