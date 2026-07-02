<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final class BuyOneGetSecondHalfPrice implements Offer
{
    public function __construct(private readonly string $productCode)
    {
    }

    public function discount(array $quantities, Catalogue $catalogue): Money
    {
        $pairs = intdiv($quantities[$this->productCode] ?? 0, 2);

        if ($pairs === 0) {
            return Money::zero();
        }

        return $catalogue->get($this->productCode)->price->half()->multiply($pairs);
    }
}
