<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Basket\Basket;
use App\Domain\Basket\Coupons;
use App\Http\Requests\BasketTotalRequest;
use Illuminate\Http\JsonResponse;

final class BasketTotalController
{
    public function __invoke(BasketTotalRequest $request, Basket $basket, Coupons $coupons): JsonResponse
    {
        foreach ($request->items() as $code) {
            $basket->add($code);
        }

        $code = $request->couponCode();
        $totals = $basket->totals($code === null ? null : $coupons->find($code));

        return new JsonResponse([
            'subtotal' => (string) $totals->subtotal,
            'discount' => (string) $totals->discount,
            'coupon_discount' => (string) $totals->couponDiscount,
            'delivery' => (string) $totals->delivery,
            'total' => (string) $totals->total,
        ]);
    }
}
