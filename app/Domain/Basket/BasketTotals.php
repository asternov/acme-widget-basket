<?php

declare(strict_types=1);

namespace App\Domain\Basket;

/**
 * Every amount here is cent-precise, and the columns reconcile:
 * subtotal - discount + delivery = total.
 */
final class BasketTotals
{
    public function __construct(
        public readonly Money $subtotal,
        public readonly Money $discount,
        public readonly Money $delivery,
        public readonly Money $total,
    ) {
    }
}
