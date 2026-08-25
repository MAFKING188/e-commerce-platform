# PayPal UX Fixes — 2026-08-25

**Source of the issue:** `docs/AUDIT-2026-08-20-full-app-sweep.md` §2 MEDIUM (lines 48–59),
corroborated by `docs/UI-UX-EVALUATION-2026-08-24.md` §1.1 #4. Both flagged as
**deferred**. Re-verified against current code before fixing.

**Goal:** eliminate broken/silent PayPal flows and remove leftover donation noise.

---

## Finding vs. reality check

| # | Doc claim | Status in current code | Action |
|---|-----------|------------------------|--------|
| 1 | Capture matches on capture-id, not order-id — payment never confirmed | **Already correct.** `capture()` matches `transaction_id` against PayPal `token` (order id), not the capture id. | No change needed — verified. |
| 2 | `capture()` has no try/catch — 500 on API/SMTP failure | **Confirmed present.** | Fixed (A) |
| 3 | Return-URL errors invisible (silent failures) | **Confirmed.** `orders/index` rendered no `$errors` banner. | Fixed (B) |
| 4 | Double-click creates duplicate pending PayPal payment | **Confirmed (newly spotted).** `store()` only blocked on `order.status`, which stays `pending` during PayPal redirect. | Fixed (C) |
| 5 | "Support Project" PayPal donation banner in order history | **Confirmed present** (`orders/index.blade.php:15`); footer/about links were removed earlier but this one remained. | Fixed (D) |

---

## Fix A — capture() error handling (PaymentController::capture)

**File:** `Modules/MarketplacePipeline/app/Http/Controllers/PaymentController.php`

Wrapped the PayPal API call (`capturePaymentOrder`), the DB transaction, and the
`PaymentSuccess` receipt mail in a single `try/catch (\Throwable $e)`.

- On any exception: log with `payment_id` + message, redirect to `orders.index`
  with a friendly `withErrors(...)` (rendered by the global toast + new banner).
- Idempotency guard (already paid -> "already completed") is preserved and now
  also protects the buyer on retry if the capture succeeded but a post-capture
  step (e.g. mail) failed.
- Known-failure branch (`status !== COMPLETED`) keeps `withErrors` so existing
  tests (`assertSessionHasErrors`) stay green.

## Fix C — duplicate-payment guard + button lock

**Controller:** `PaymentController::store` — added a guard before the PayPal API
call: if a `pending` PayPal `Payment` already exists for the order, return
`withErrors('A PayPal payment is already in progress...')` (no external call made).

**View:** `Modules/MarketplacePipeline/resources/views/orders/index.blade.php`
- Added `class="paypal-checkout-form"` to the PayPal form.
- Added a `@section('scripts')` block that disables the submit button and swaps
  its label to "Redirecting to PayPal..." on submit (prevents double-submit).

## Fix B — surface errors on order history

**View:** `Modules/MarketplacePipeline/resources/views/orders/index.blade.php`
- Added a styled `.checkout-error` banner (role="alert") that renders
  `session('error')` and all `$errors->all()` at the top of the order list.
- The global toast in `app-layout.blade.php` already renders both, but the
  in-page banner makes failures explicitly visible (the documented "silent
  failure" gap). The cart view already rendered `$errors`, so no change there.

## Fix D — remove leftover donation banner

**View:** `orders/index.blade.php` — deleted the `Support Project` PayPal
(`paypal.com/ncp/payment/...`) banner block from order history. (Footer/about
links were removed earlier; this one was missed.)

---

## Files changed

1. `Modules/MarketplacePipeline/app/Http/Controllers/PaymentController.php` (A, C)
2. `Modules/MarketplacePipeline/resources/views/orders/index.blade.php` (B, C, D)
3. `Modules/MarketplacePipeline/tests/Feature/MoneyClusterTest.php` (new test)

## Tests

- New: `test_duplicate_paypal_checkout_is_rejected` — asserts a second
  `paypal.store` POST for an order with a pending PayPal payment is rejected and
  no second payment row is created.
- `php artisan test --filter=MoneyCluster` -> **12 passed (41 assertions)**.
- Full suite: PayPal + new test pass. **20 pre-existing failures are
  `ExceptionHandlerTest`** across all modules — they belong to the separate
  error-handling feature (2026-08-31 commits) and are unrelated to this work.
  Left untouched.

## Deployment

Not yet deployed. Deploy only the 3 changed files via the standard prod flow
(stash + pull + `view:clear`). Confirm before pushing, since the repo currently
has 20 unrelated failing `ExceptionHandlerTest` cases from another feature.
