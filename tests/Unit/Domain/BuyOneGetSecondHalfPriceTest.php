<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Basket\BuyOneGetSecondHalfPrice;
use App\Domain\Basket\Catalogue;
use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BuyOneGetSecondHalfPriceTest extends TestCase
{
    #[DataProvider('redWidgetQuantities')]
    public function test_discounts_half_price_for_every_pair(int $quantity, int $expectedHalfPrices): void
    {
        $offer = new BuyOneGetSecondHalfPrice('R01');
        $halfPrice = Money::fromCents(3295)->half();

        $discount = $offer->discount(['R01' => $quantity], $this->catalogue());

        $this->assertTrue($discount->equals($halfPrice->multiply($expectedHalfPrices)));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function redWidgetQuantities(): array
    {
        return [
            'single item, no pair' => [1, 0],
            'one pair' => [2, 1],
            'a pair and a spare' => [3, 1],
            'two pairs' => [4, 2],
        ];
    }

    public function test_ignores_other_products(): void
    {
        $offer = new BuyOneGetSecondHalfPrice('R01');

        $discount = $offer->discount(['G01' => 4, 'B01' => 2], $this->catalogue());

        $this->assertTrue($discount->equals(Money::zero()));
    }

    private function catalogue(): Catalogue
    {
        return new Catalogue([
            new Product('R01', 'Red Widget', Money::fromCents(3295)),
            new Product('G01', 'Green Widget', Money::fromCents(2495)),
            new Product('B01', 'Blue Widget', Money::fromCents(795)),
        ]);
    }
}
