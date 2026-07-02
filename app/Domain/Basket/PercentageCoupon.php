<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final class PercentageCoupon
{
    public function __construct(
        public readonly string $code,
        public readonly int $percentOff,
    ) {
        if ($percentOff < 1 || $percentOff > 100) {
            throw new InvalidArgumentException('A coupon must take off between 1 and 100 percent.');
        }
    }

    /**
     * The discount is floored to a whole cent, so it never exceeds
     * the advertised percentage of what the customer actually pays.
     */
    public function discount(Money $goods): Money
    {
        return $goods->percent($this->percentOff);
    }
}
