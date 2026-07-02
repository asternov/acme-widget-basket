# Acme Widget Co - Basket Proof of Concept

Proof of concept for the Acme Widget Co sales system. Laravel backend that owns
all the pricing rules, small React + TypeScript UI on top.

Live demo: https://acme.arjuna.pro

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
next to `php artisan serve` instead of `npm run build`.

## Tests

```
vendor/bin/phpunit     # backend: domain + API
npm test               # frontend: vitest + Testing Library
npm run typecheck
```

The four example baskets from the brief are pinned twice: as domain unit tests
(`tests/Unit/Domain/BasketTest.php`) and through the HTTP API
(`tests/Feature/BasketApiTest.php`). The React side has component and hook
tests next to the code they cover (`resources/js/**/*.test.tsx`).

## How it works

All pricing logic lives in a framework-free domain layer, `app/Domain/Basket`.
Nothing in there knows about Laravel:

- `Basket` - the interface from the brief. Constructed with the product
  catalogue, a delivery policy and a list of offers; `add()` takes a product
  code, `total()` prices the basket. `totals()` returns the full
  subtotal / discount / delivery breakdown the UI shows.
- `Money` - exact amounts stored as an integer number of half cents. No floats
  touch money anywhere in this codebase.
- `TieredDelivery` - delivery charge rules as data: a list of "from this
  payable amount, delivery costs X" tiers.
- `Offer` + `BuyOneGetSecondHalfPrice` - offers are pluggable strategies, the
  red widget offer is one of them, parameterised by product code.
- `PercentageCoupon` + `Coupons` - coupon codes (`WIDGET10`, `ACME20`) applied
  on top of offers. The discount is floored to a whole cent and the delivery
  tier is re-checked against what the customer actually pays.

The catalogue, delivery tiers and offers are configuration (`config/acme.php`),
not code. `AppServiceProvider` wires them into the domain, and the HTTP layer
is two thin endpoints:

- `GET /api/products` - the catalogue for the UI.
- `POST /api/basket/total` - prices a list of product codes (plus an optional
  coupon code) and returns the breakdown. Codes are validated against the
  configuration, anything unknown is a 422.

The React app (`resources/js`) keeps no pricing knowledge at all. It sends the
chosen codes to the API and renders whatever comes back.

## Money and rounding

The half-price offer produces amounts like $16.475 that neither floats nor
plain cents can hold exactly, so `Money` keeps integer half cents internally.

I derived the rounding policy from the example baskets before writing any
code. For `R01, R01`: 65.90 - 16.475 = 49.425, and the expected total is 54.37
with the under-$50 delivery charge. That only works if 49.425 truncates down
to 49.42 *before* the delivery tier is chosen. The last example confirms it:
114.75 - 16.475 = 98.275 -> 98.27, free delivery.

So the rules are:

1. Sum full-price items, subtract exact offer discounts.
2. Truncate the result down to a whole cent, in the customer's favour.
3. Pick the delivery tier from that truncated goods total.

The discount shown in the breakdown is defined as `subtotal - goods total`, so
the displayed columns always reconcile:
subtotal - discount - coupon + delivery = total.

Coupons follow the same discipline: the percentage is taken from the
cent-precise goods total and floored to a whole cent, so a "10% off" coupon
never takes off more than 10%.

## Assumptions

- Sub-cent remainders are truncated at basket level, not per offer. This is
  the simplest policy consistent with all four example totals (a per-offer
  floor would price the last example at 98.28).
- "Amount spent" for the delivery tiers means the discounted goods total, as
  the `R01, R01` example confirms (49.42 -> $4.95 delivery).
- An empty basket prices at $0.00 and ships nothing.
- A coupon applies after product offers, and delivery is charged on the amount
  the customer actually pays. So a coupon can bring a basket back under a
  delivery threshold.
- The pricing API is stateless: the client sends product codes, the server
  prices them. A real sales system would persist baskets, but that adds
  nothing to a pricing proof of concept.
- Prices are single-currency dollar amounts, as in the brief.

## Production notes

Things this proof of concept deliberately leaves out, and what I would build
first in a real sales system:

- **Orders and idempotency.** Checkout becomes a `POST /api/orders` that
  persists the priced basket and accepts an `Idempotency-Key`, storing and
  replaying the first result so a retried request can never charge twice.
- **Payment webhooks.** Provider notifications get verified against a
  signature, deduplicated by event id (a unique constraint, not
  check-then-act) and processed inside one database transaction. Payment
  providers routinely retry the same notification for hours.
- **Multi-party money.** Once affiliates or revenue splits enter the picture,
  every order fans out into several ledger entries. The integer-money and
  explicit-rounding discipline in `Money` is what keeps those splits adding
  up.
