<?php

declare(strict_types=1);

namespace App\Domain\Basket;

interface DeliveryPolicy
{
    public function cost(Money $amountPayable): Money;
}
