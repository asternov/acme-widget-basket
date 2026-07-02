<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final class Catalogue
{
    /** @var array<string, Product> */
    private array $products = [];

    /**
     * @param list<Product> $products
     */
    public function __construct(array $products)
    {
        foreach ($products as $product) {
            $this->products[$product->code] = $product;
        }
    }

    public function get(string $code): Product
    {
        return $this->products[$code] ?? throw new UnknownProduct($code);
    }

    /**
     * @return list<Product>
     */
    public function all(): array
    {
        return array_values($this->products);
    }
}
