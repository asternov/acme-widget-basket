<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Basket\Money;
use App\Domain\Basket\TieredDelivery;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TieredDeliveryTest extends TestCase
{
    #[DataProvider('payableAmounts')]
    public function test_charges_the_tier_matching_the_amount_payable(int $payableCents, string $expectedCost): void
    {
        $cost = $this->policy()->cost(Money::fromCents($payableCents));

        $this->assertSame($expectedCost, (string) $cost);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function payableAmounts(): array
    {
        return [
            'small order' => [1000, '4.95'],
            'just under 50' => [4999, '4.95'],
            'exactly 50' => [5000, '2.95'],
            'just under 90' => [8999, '2.95'],
            'exactly 90' => [9000, '0.00'],
            'well over 90' => [15000, '0.00'],
        ];
    }

    public function test_accepts_tiers_in_any_order(): void
    {
        $policy = new TieredDelivery([
            ['from_cents' => 9000, 'cost_cents' => 0],
            ['from_cents' => 0, 'cost_cents' => 495],
            ['from_cents' => 5000, 'cost_cents' => 295],
        ]);

        $this->assertSame('2.95', (string) $policy->cost(Money::fromCents(6000)));
    }

    public function test_rejects_tiers_that_leave_small_amounts_unpriced(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TieredDelivery([['from_cents' => 5000, 'cost_cents' => 295]]);
    }

    private function policy(): TieredDelivery
    {
        return new TieredDelivery([
            ['from_cents' => 0, 'cost_cents' => 495],
            ['from_cents' => 5000, 'cost_cents' => 295],
            ['from_cents' => 9000, 'cost_cents' => 0],
        ]);
    }
}
