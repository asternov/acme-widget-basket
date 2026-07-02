<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Basket\Catalogue;
use App\Domain\Basket\Product;
use Illuminate\Http\JsonResponse;

final class ProductsController
{
    public function __invoke(Catalogue $catalogue): JsonResponse
    {
        return new JsonResponse([
            'products' => array_map(
                fn (Product $product): array => [
                    'code' => $product->code,
                    'name' => $product->name,
                    'price' => (string) $product->price,
                ],
                $catalogue->all(),
            ),
        ]);
    }
}
