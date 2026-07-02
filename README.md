# Acme Widget Co — Basket Proof of Concept

A proof of concept for the Acme Widget Co sales system: a Laravel backend that
owns all pricing rules, with a small React + TypeScript UI on top.

## Requirements

- PHP 8.3+ with Composer
- Node.js 22+

## Getting started

```
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan serve
```

Open http://127.0.0.1:8000. When working on the frontend, run `npm run dev`
alongside `php artisan serve` instead of `npm run build`.

## Tests

```
vendor/bin/phpunit     # backend: domain + API
npm test               # frontend: vitest + Testing Library
npm run typecheck
```

The four example baskets from the brief are covered twice: as domain unit tests
(`tests/Unit/Domain/BasketTest.php`) and through the HTTP API
(`tests/Feature/BasketApiTest.php`). The React side is covered by component and
hook tests next to the code they test (`resources/js/**/*.test.tsx`).

## How it works

All pricing logic lives in a framework-free domain layer under
`app/Domain/Basket`:

- `Basket` — the interface the brief asks for. It is constructed with the
  product catalogue, a delivery policy and a list of offers; `add()` takes a
  product code and `total()` prices the basket. `totals()` returns the full
  subtotal / discount / delivery breakdown that the UI shows.
- `Money` — exact monetary amounts stored as an integer number of half cents.
  No floats touch money anywhere in the codebase.
- `TieredDelivery` — the delivery charge rules as data: a list of
  "from this payable amount, delivery costs X" tiers.
- `Offer` + `BuyOneGetSecondHalfPrice` — offers are pluggable strategies; the
  red widget offer is one implementation, parameterised by product code.
- `PercentageCoupon` + `Coupons` — coupon codes (`WIDGET10`, `ACME20`) applied
  on top of offers. The discount is floored to a whole cent, and the delivery
  tier is re-evaluated from what the customer actually pays after the coupon.

The catalogue, delivery tiers and active offers are configuration
(`config/acme.php`), not code. `AppServiceProvider` wires them into the domain,
and the HTTP layer is two thin endpoints:

- `GET /api/products` — the catalogue for the UI.
- `POST /api/basket/total` — prices a list of product codes (plus an optional
  coupon code) and returns the breakdown. Product and coupon codes are
  validated against the configuration (422 otherwise).

The React app (`resources/js`) keeps no pricing knowledge at all: it sends the
chosen product codes to the API and renders whatever comes back.

## Money and rounding

The half-price offer produces amounts like $16.475 that no float or plain-cents
representation can hold exactly, so `Money` uses integer half cents internally.

The rounding policy is derived from the example baskets in the brief. For
`R01, R01`: 65.90 − 16.475 = 49.425. The expected total is 54.37 with the
under-$50 delivery charge, which means 49.425 must truncate down to 49.42
*before* the delivery tier is chosen. The same policy explains
`B01, B01, R01, R01, R01`: 114.75 − 16.475 = 98.275 → 98.27, free delivery.

So the rules are:

1. Sum full-price items, subtract exact offer discounts.
2. Truncate the result down to a whole cent (in the customer's favour).
3. Pick the delivery tier from that truncated goods total.

The discount shown in the breakdown is defined as `subtotal − goods total`, so
the displayed columns always reconcile:
subtotal − discount − coupon + delivery = total.

Coupons follow the same discipline: the percentage is taken from the
cent-precise goods total and floored to a whole cent, so a "10% off" coupon
never takes off more than 10%.

## Assumptions

- Sub-cent remainders are truncated at basket level, not per offer — the
  simplest policy consistent with all four example totals (a per-offer floor
  would price the last example at 98.28).
- "Amount spent" for the delivery tiers means the discounted goods total, as
  confirmed by the `R01, R01` example (49.42 → $4.95 delivery).
- An empty basket prices at $0.00 and ships nothing.
- A coupon applies after product offers, and delivery is charged on the amount
  the customer actually pays — so a coupon can bring a basket back under a
  delivery threshold.
- The pricing API is stateless: the client sends product codes, the server
  prices them. A real sales system would persist baskets, but that adds nothing
  to a pricing proof of concept.
- Prices are single-currency dollar amounts, as in the brief.

## Production notes

Things this proof of concept deliberately leaves out, and what they would look
like in a real sales system:

- **Orders and idempotency.** Checkout would be a `POST /api/orders` that
  persists the priced basket and accepts an `Idempotency-Key`, storing and
  replaying the first result so a retried request can never charge twice.
- **Payment webhooks.** Provider notifications would be verified against a
  signature, deduplicated by event id (a unique constraint, not check-then-act),
  and processed inside one database transaction — payment providers routinely
  retry the same notification for hours.
- **Multi-party money.** Once affiliates or revenue splits enter the picture,
  every order fans out into several ledger entries; the integer-money and
  explicit-rounding discipline in `Money` is what keeps those splits adding up.
