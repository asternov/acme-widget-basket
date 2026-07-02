<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final class UnknownProduct extends InvalidArgumentException
{
    public function __construct(public readonly string $productCode)
    {
        parent::__construct(sprintf('Unknown product code "%s".', $productCode));
    }
}
