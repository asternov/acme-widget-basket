<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final class Basket
{
    /** @var array<string, int> product code => quantity */
    private array $quantities = [];

    /**
     * @param list<Offer> $offers
     */
    public function __construct(
        private readonly Catalogue $catalogue,
        private readonly DeliveryPolicy $deliveryPolicy,
        private readonly array $offers = [],
    ) {
    }

    public function add(string $productCode): void
    {
        $code = $this->catalogue->get($productCode)->code;

        $this->quantities[$code] = ($this->quantities[$code] ?? 0) + 1;
    }

    public function total(): Money
    {
        return $this->totals()->total;
    }

    public function totals(?PercentageCoupon $coupon = null): BasketTotals
    {
        if ($this->quantities === []) {
            return new BasketTotals(Money::zero(), Money::zero(), Money::zero(), Money::zero(), Money::zero());
        }

        $subtotal = Money::zero();
        foreach ($this->quantities as $code => $quantity) {
            $subtotal = $subtotal->add($this->catalogue->get($code)->price->multiply($quantity));
        }

        $discounted = $subtotal;
        foreach ($this->offers as $offer) {
            $discounted = $discounted->subtract($offer->discount($this->quantities, $this->catalogue));
        }

        // Sub-cent remainders are truncated in the customer's favour; this
        // truncate-then-coupon-then-delivery ordering is derived from the
        // spec examples.
        $goods = $discounted->truncateToCent();
        $couponDiscount = $coupon?->discount($goods) ?? Money::zero();
        $payable = $goods->subtract($couponDiscount);
        $delivery = $this->deliveryPolicy->cost($payable);

        return new BasketTotals(
            subtotal: $subtotal,
            discount: $subtotal->subtract($goods),
            couponDiscount: $couponDiscount,
            delivery: $delivery,
            total: $payable->add($delivery),
        );
    }
}
