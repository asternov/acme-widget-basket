<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Basket\Basket;
use App\Domain\Basket\BuyOneGetSecondHalfPrice;
use App\Domain\Basket\Catalogue;
use App\Domain\Basket\Money;
use App\Domain\Basket\PercentageCoupon;
use App\Domain\Basket\Product;
use App\Domain\Basket\TieredDelivery;
use App\Domain\Basket\UnknownProduct;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasketTest extends TestCase
{
    /**
     * @param list<string> $codes
     */
    #[DataProvider('specExamples')]
    public function test_matches_the_spec_examples(array $codes, string $expectedTotal): void
    {
        $basket = $this->basket();

        foreach ($codes as $code) {
            $basket->add($code);
        }

        $this->assertSame($expectedTotal, (string) $basket->total());
    }

    /**
     * @return array<string, array{list<string>, string}>
     */
    public static function specExamples(): array
    {
        return [
            'B01, G01' => [['B01', 'G01'], '37.85'],
            'R01, R01' => [['R01', 'R01'], '54.37'],
            'R01, G01' => [['R01', 'G01'], '60.85'],
            'B01, B01, R01, R01, R01' => [['B01', 'B01', 'R01', 'R01', 'R01'], '98.27'],
        ];
    }

    public function test_an_empty_basket_costs_nothing_and_ships_nothing(): void
    {
        $totals = $this->basket()->totals();

        $this->assertSame('0.00', (string) $totals->subtotal);
        $this->assertSame('0.00', (string) $totals->delivery);
        $this->assertSame('0.00', (string) $totals->total);
    }

    public function test_rejects_unknown_product_codes(): void
    {
        $this->expectException(UnknownProduct::class);
        $this->expectExceptionMessage('X99');

        $this->basket()->add('X99');
    }

    public function test_delivery_tier_is_chosen_after_the_discount_is_applied(): void
    {
        $basket = $this->basket();
        $basket->add('R01');
        $basket->add('R01');

        $totals = $basket->totals();

        $this->assertSame('65.90', (string) $totals->subtotal);
        $this->assertSame('4.95', (string) $totals->delivery);
    }

    public function test_breakdown_always_reconciles_to_the_total(): void
    {
        $basket = $this->basket();
        foreach (['B01', 'B01', 'R01', 'R01', 'R01'] as $code) {
            $basket->add($code);
        }

        $totals = $basket->totals();

        $this->assertSame('114.75', (string) $totals->subtotal);
        $this->assertSame('16.48', (string) $totals->discount);
        $this->assertSame('0.00', (string) $totals->delivery);
        $this->assertSame('98.27', (string) $totals->total);
        $this->assertTrue(
            $totals->subtotal->subtract($totals->discount)->add($totals->delivery)->equals($totals->total),
        );
    }

    public function test_free_delivery_starts_at_exactly_ninety_dollars(): void
    {
        $catalogue = new Catalogue([new Product('N01', 'Ninety', Money::fromCents(9000))]);
        $basket = new Basket($catalogue, $this->deliveryPolicy());
        $basket->add('N01');

        $totals = $basket->totals();

        $this->assertSame('0.00', (string) $totals->delivery);
        $this->assertSame('90.00', (string) $totals->total);
    }

    public function test_reduced_delivery_starts_at_exactly_fifty_dollars(): void
    {
        $catalogue = new Catalogue([new Product('F01', 'Fifty', Money::fromCents(5000))]);
        $basket = new Basket($catalogue, $this->deliveryPolicy());
        $basket->add('F01');

        $this->assertSame('2.95', (string) $basket->totals()->delivery);
    }

    public function test_two_pairs_of_red_widgets_get_two_discounts(): void
    {
        $basket = $this->basket();
        foreach (['R01', 'R01', 'R01', 'R01'] as $code) {
            $basket->add($code);
        }

        $totals = $basket->totals();

        $this->assertSame('131.80', (string) $totals->subtotal);
        $this->assertSame('32.95', (string) $totals->discount);
        $this->assertSame('98.85', (string) $totals->total);
    }

    public function test_a_coupon_discounts_the_goods_and_can_bring_back_a_delivery_charge(): void
    {
        $basket = $this->basket();
        foreach (['B01', 'B01', 'R01', 'R01', 'R01'] as $code) {
            $basket->add($code);
        }

        $totals = $basket->totals(new PercentageCoupon('WIDGET10', 10));

        $this->assertSame('114.75', (string) $totals->subtotal);
        $this->assertSame('16.48', (string) $totals->discount);
        $this->assertSame('9.82', (string) $totals->couponDiscount);
        $this->assertSame('2.95', (string) $totals->delivery);
        $this->assertSame('91.40', (string) $totals->total);
        $this->assertTrue(
            $totals->subtotal
                ->subtract($totals->discount)
                ->subtract($totals->couponDiscount)
                ->add($totals->delivery)
                ->equals($totals->total),
        );
    }

    public function test_without_a_coupon_the_coupon_discount_is_zero(): void
    {
        $basket = $this->basket();
        $basket->add('B01');

        $this->assertSame('0.00', (string) $basket->totals()->couponDiscount);
    }

    private function basket(): Basket
    {
        return new Basket(
            new Catalogue([
                new Product('R01', 'Red Widget', Money::fromCents(3295)),
                new Product('G01', 'Green Widget', Money::fromCents(2495)),
                new Product('B01', 'Blue Widget', Money::fromCents(795)),
            ]),
            $this->deliveryPolicy(),
            [new BuyOneGetSecondHalfPrice('R01')],
        );
    }

    private function deliveryPolicy(): TieredDelivery
    {
        return new TieredDelivery([
            ['from_cents' => 0, 'cost_cents' => 495],
            ['from_cents' => 5000, 'cost_cents' => 295],
            ['from_cents' => 9000, 'cost_cents' => 0],
        ]);
    }
}
