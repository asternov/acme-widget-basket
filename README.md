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
vendor/bin/phpunit
npm run typecheck
```

The four example baskets from the brief are covered twice: as domain unit tests
(`tests/Unit/Domain/BasketTest.php`) and through the HTTP API
(`tests/Feature/BasketApiTest.php`).

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
  "from this subtotal, delivery costs X" tiers.
- `Offer` + `BuyOneGetSecondHalfPrice` — offers are pluggable strategies; the
  red widget offer is one implementation, parameterised by product code.

The catalogue, delivery tiers and active offers are configuration
(`config/acme.php`), not code. `AppServiceProvider` wires them into the domain,
and the HTTP layer is two thin endpoints:

- `GET /api/products` — the catalogue for the UI.
- `POST /api/basket/total` — prices a list of product codes and returns the
  breakdown. Product codes are validated against the catalogue (422 otherwise).

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
the displayed columns always reconcile: subtotal − discount + delivery = total.

## Assumptions

- Sub-cent remainders are truncated at basket level, not per offer — the
  simplest policy consistent with all four example totals (a per-offer floor
  would price the last example at 98.28).
- "Amount spent" for the delivery tiers means the discounted goods total, as
  confirmed by the `R01, R01` example (49.42 → $4.95 delivery).
- An empty basket prices at $0.00 and ships nothing.
- The pricing API is stateless: the client sends product codes, the server
  prices them. A real sales system would persist baskets, but that adds nothing
  to a pricing proof of concept.
- Prices are single-currency dollar amounts, as in the brief.
