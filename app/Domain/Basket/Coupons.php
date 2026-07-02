<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final class Coupons
{
    /** @var array<string, PercentageCoupon> */
    private array $coupons = [];

    /**
     * @param list<PercentageCoupon> $coupons
     */
    public function __construct(array $coupons)
    {
        foreach ($coupons as $coupon) {
            $this->coupons[$coupon->code] = $coupon;
        }
    }

    public function find(string $code): ?PercentageCoupon
    {
        return $this->coupons[$code] ?? null;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->coupons);
    }
}
