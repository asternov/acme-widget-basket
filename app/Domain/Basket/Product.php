<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final class Product
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly Money $price,
    ) {
    }
}
