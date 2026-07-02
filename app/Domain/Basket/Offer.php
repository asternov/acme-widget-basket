<?php

declare(strict_types=1);

namespace App\Domain\Basket;

interface Offer
{
    /**
     * @param array<string, int> $quantities product code => quantity in the basket
     */
    public function discount(array $quantities, Catalogue $catalogue): Money;
}
