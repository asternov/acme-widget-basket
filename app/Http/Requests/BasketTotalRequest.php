<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Basket\Catalogue;
use App\Domain\Basket\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BasketTotalRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(Catalogue $catalogue): array
    {
        $codes = array_map(
            fn (Product $product): string => $product->code,
            $catalogue->all(),
        );

        return [
            'items' => ['present', 'array'],
            'items.*' => ['string', Rule::in($codes)],
        ];
    }

    /**
     * @return list<string>
     */
    public function items(): array
    {
        return $this->validated('items');
    }
}
