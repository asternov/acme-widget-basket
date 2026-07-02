<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;
use LogicException;

/**
 * An exact monetary amount, stored as an integer number of half cents.
 *
 * The half-price offer produces amounts like $16.475 that must survive
 * until the basket-level truncation. Half a cent is the smallest unit
 * this domain can produce, so integer half cents keep every intermediate
 * value exact without floats or arbitrary-precision math.
 */
final class Money
{
    private function __construct(private readonly int $halfCents)
    {
        if ($halfCents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents * 2);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->halfCents + $other->halfCents);
    }

    public function subtract(self $other): self
    {
        return new self($this->halfCents - $other->halfCents);
    }

    public function multiply(int $times): self
    {
        return new self($this->halfCents * $times);
    }

    public function half(): self
    {
        if ($this->halfCents % 2 !== 0) {
            throw new LogicException('Halving this amount would lose sub-half-cent precision.');
        }

        return new self(intdiv($this->halfCents, 2));
    }

    public function truncateToCent(): self
    {
        return new self($this->halfCents - $this->halfCents % 2);
    }

    public function isAtLeast(self $other): bool
    {
        return $this->halfCents >= $other->halfCents;
    }

    public function equals(self $other): bool
    {
        return $this->halfCents === $other->halfCents;
    }

    public function cents(): int
    {
        if ($this->halfCents % 2 !== 0) {
            throw new LogicException('Amount has half-cent precision; truncate it to whole cents first.');
        }

        return intdiv($this->halfCents, 2);
    }

    public function __toString(): string
    {
        $cents = $this->cents();

        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
