<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Basket\Basket;
use App\Http\Requests\BasketTotalRequest;
use Illuminate\Http\JsonResponse;

final class BasketTotalController
{
    public function __invoke(BasketTotalRequest $request, Basket $basket): JsonResponse
    {
        foreach ($request->items() as $code) {
            $basket->add($code);
        }

        $totals = $basket->totals();

        return new JsonResponse([
            'subtotal' => (string) $totals->subtotal,
            'discount' => (string) $totals->discount,
            'delivery' => (string) $totals->delivery,
            'total' => (string) $totals->total,
        ]);
    }
}
