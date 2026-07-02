<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final class TieredDelivery implements DeliveryPolicy
{
    /** @var list<array{from: Money, cost: Money}> ordered by ascending threshold */
    private array $tiers = [];

    /**
     * @param list<array{from_cents: int, cost_cents: int}> $tiers
     */
    public function __construct(array $tiers)
    {
        usort($tiers, fn (array $a, array $b): int => $a['from_cents'] <=> $b['from_cents']);

        if ($tiers === [] || $tiers[0]['from_cents'] !== 0) {
            throw new InvalidArgumentException('Delivery tiers must start at zero.');
        }

        foreach ($tiers as $tier) {
            $this->tiers[] = [
                'from' => Money::fromCents($tier['from_cents']),
                'cost' => Money::fromCents($tier['cost_cents']),
            ];
        }
    }

    public function cost(Money $amountPayable): Money
    {
        $cost = Money::zero();

        foreach ($this->tiers as $tier) {
            if ($amountPayable->isAtLeast($tier['from'])) {
                $cost = $tier['cost'];
            }
        }

        return $cost;
    }
}
