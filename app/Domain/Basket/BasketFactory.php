<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

/**
 * Builds the domain objects from plain configuration arrays,
 * so the catalogue and pricing rules stay data, not code.
 */
final class BasketFactory
{
    /**
     * @param array{
     *     delivery_tiers: list<array{from_cents: int, cost_cents: int}>,
     *     offers: list<array<string, string>>,
     * } $config
     */
    public static function basket(Catalogue $catalogue, array $config): Basket
    {
        return new Basket(
            $catalogue,
            new TieredDelivery($config['delivery_tiers']),
            self::offers($config['offers']),
        );
    }

    /**
     * @param list<array{code: string, name: string, price_cents: int}> $products
     */
    public static function catalogue(array $products): Catalogue
    {
        return new Catalogue(array_map(
            fn (array $product): Product => new Product(
                $product['code'],
                $product['name'],
                Money::fromCents($product['price_cents']),
            ),
            $products,
        ));
    }

    /**
     * @param list<array<string, string>> $offers
     *
     * @return list<Offer>
     */
    public static function offers(array $offers): array
    {
        return array_map(
            fn (array $offer): Offer => match ($offer['type']) {
                'buy_one_get_second_half_price' => new BuyOneGetSecondHalfPrice($offer['product']),
                default => throw new InvalidArgumentException(
                    sprintf('Unknown offer type "%s".', $offer['type']),
                ),
            },
            $offers,
        );
    }
}
