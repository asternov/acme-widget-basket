<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BasketApiTest extends TestCase
{
    public function test_lists_the_product_catalogue(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertOk()->assertExactJson([
            'products' => [
                ['code' => 'R01', 'name' => 'Red Widget', 'price' => '32.95'],
                ['code' => 'G01', 'name' => 'Green Widget', 'price' => '24.95'],
                ['code' => 'B01', 'name' => 'Blue Widget', 'price' => '7.95'],
            ],
        ]);
    }

    /**
     * @param list<string> $items
     */
    #[DataProvider('specExamples')]
    public function test_prices_the_spec_example_baskets(array $items, string $total): void
    {
        $response = $this->postJson('/api/basket/total', ['items' => $items]);

        $response->assertOk()->assertJsonPath('total', $total);
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

    public function test_returns_the_full_breakdown(): void
    {
        $response = $this->postJson('/api/basket/total', ['items' => ['R01', 'R01']]);

        $response->assertOk()->assertExactJson([
            'subtotal' => '65.90',
            'discount' => '16.48',
            'coupon_discount' => '0.00',
            'delivery' => '4.95',
            'total' => '54.37',
        ]);
    }

    public function test_applies_a_coupon_and_reprices_delivery_from_the_payable_amount(): void
    {
        $response = $this->postJson('/api/basket/total', [
            'items' => ['B01', 'B01', 'R01', 'R01', 'R01'],
            'coupon' => 'WIDGET10',
        ]);

        $response->assertOk()->assertExactJson([
            'subtotal' => '114.75',
            'discount' => '16.48',
            'coupon_discount' => '9.82',
            'delivery' => '2.95',
            'total' => '91.40',
        ]);
    }

    public function test_rejects_an_unknown_coupon_code(): void
    {
        $response = $this->postJson('/api/basket/total', ['items' => ['R01'], 'coupon' => 'NOPE']);

        $response->assertUnprocessable()->assertJsonValidationErrors('coupon');
    }

    public function test_prices_an_empty_basket_at_zero(): void
    {
        $response = $this->postJson('/api/basket/total', ['items' => []]);

        $response->assertOk()->assertJsonPath('total', '0.00');
    }

    public function test_rejects_unknown_product_codes(): void
    {
        $response = $this->postJson('/api/basket/total', ['items' => ['R01', 'X99']]);

        $response->assertUnprocessable()->assertJsonValidationErrors('items.1');
    }

    public function test_rejects_a_request_without_items(): void
    {
        $response = $this->postJson('/api/basket/total', []);

        $response->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_rejects_items_that_are_not_a_list_of_codes(): void
    {
        $this->postJson('/api/basket/total', ['items' => 'R01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->postJson('/api/basket/total', ['items' => ['first' => 'R01']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }
}
