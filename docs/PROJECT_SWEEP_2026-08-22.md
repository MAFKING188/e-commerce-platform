# SmartShop (LUWI) — Project Sweep & Engineer Handoff Report

> **Generated:** 2026-08-22 · HEAD `919f8e2` · branch `main`
> **Purpose:** A new engineer should be able to read ONLY this file and know what the
> project is, how it is structured, its exact size, what is wired vs unwired, and what
> to improve next.
> **Companions:** `docs/PROJECT_ARCHITECTURE.md` (deep architecture),
> `docs/AUDIT-2026-08-20-full-app-sweep.md` (UI/flow audit),
> `PROJECT_REPORT.txt` (session-by-session changelog §1–19).

---

## 1. Executive Summary

SmartShop ("LUWI") is a production-deployed **multi-vendor e-commerce platform**
(Laravel modular monolith) at **https://smartshop-luwi.tech** — DigitalOcean droplet,
nginx + PHP-FPM 8.3 + MySQL, SSL via Certbot, queue workers under Supervisor.

- **199 commits** since 2026-05-18 · **167 tests / 1121 assertions green** (~8.6s)
- **6 domain modules**, 31 DB tables, 156 routes, 93 blade views
- Full auth lifecycle incl. **mandatory email-OTP challenge** for admins/partners,
  Google OAuth, password reset, step-up codes at checkout
- PayPal checkout flow, payout engine (config-driven commission), partner portal with
  analytics, admin command center, **Email Center** (templated bulk mail, admin +
  buyer-scoped partner sending)
- Catalog fully curated (110 products / 7 categories), images **self-hosted** with
  immutable caching + lazy loading (perf pass 2026-08-22)

---

## 2. Architecture (Modular Monolith)

One Laravel app, one database, one deployable. Six nwidart-style modules each own
their models/controllers/services/routes/views/migrations/seeders/tests/assets.
Cross-module access = explicit model imports only. Module registration: root
composer merge-plugin (`Modules/*/composer.json`) + `bootstrap/cache/modules.php`
manifest (rebuild by deleting it or `php artisan module:enable <Name>`).

```
browser ──► nginx ──► php-fpm ──► Laravel
                                   ├─ Storefront   (guest+buyer blades)
                                   ├─ Admin Portal (/admin/*  auth+admin+active)
                                   └─ Partner Portal (/partner/* auth+partner+active)
                                            │
        ┌──────────┬──────────┬───────────┼────────────┬──────────────┐
   IdentityAccess Catalog  Marketplace PartnerHub  Telemetry    EmailCenter
   auth/2FA/admin  Delivery cart/orders/ partners/    audit logs,  templates/bulk mail
   users/profiles  catalog   payments/   profiles     rate limits  logs/history
                   reviews    payouts     dashboard
                   inventory  emails      registry
```

**Shared core (`app/`):** base Controller, `Address` model, `CurrencyMiddleware`,
`CurrencyService`, `AppLayout` view component (auto-loads every enabled module's
scss/js pair — a module WITHOUT both asset files breaks every page render).

**Key middleware aliases:** `admin`, `partner`, `2fa.pending`. Rate limiters live in
TelemetryPipeline's RouteServiceProvider (`auth`, `checkout`, `2fa*`, `email`).
Business rules in `config/shop.php` (commission rate, currency, contact email).

### Per-module inventory (controllers/models/services/mailables | migrations | blades/tests)

| Module | Owns | Ctl/Mdl/Svc/Mail | Migr | Blades | Test methods |
|---|---|---|---|---|---|
| IdentityAccess | users, auth+2FA+Google, profiles, wishlist, admin member registry, GovernanceService | 10/2/3/5 | 11 | 22 | 77 |
| CatalogDelivery | storefront, products+media, categories, reviews, partner inventory CRUD | 9/6/1/1 | 10 | 26 | 31 |
| MarketplacePipeline | cart, orders, PayPal, payouts, commerce emails, CheckoutService/PayoutService | 8/6/2/3 | 7 | 13 | 14 |
| PartnerHub | artisan entities, registry, public profiles, partner dashboard | 4/2/0/0 | 6 | 10 | 8 |
| TelemetryPipeline | audit_logs + email_logs, AnalyticsService, rate limiters, /health | 1/2/3/1 | 2 | 3 | 4 |
| EmailCenter | templates CRUD, compose/send, send history (admin + buyer-scoped partner) | 5/2/1/1 | 2 | 8 | 32 |

Totals: **38 controllers · 21 models · 11 services · 11 mailables · 42 migrations ·
13 seeders · 93 blades · 41 test files / 167 tests**

---

## 3. Numbers (exact)

| Metric | Value |
|---|---|
| Tracked files | 526 |
| App code LOC (Modules/*/app + app/) | 5,795 across 113 files |
| Test code LOC | 3,020 across 47 files (≈1:0.52 app:test) |
| Routes | 156 total (137 web / 19 api) · 150 named |
| HTTP methods | GET|HEAD 76 · POST 35 · DELETE 10 · PUT/PATCH 12 |
| DB tables | 31 (⚠ legacy `user` table exists alongside `users`) |
| Migrations | 42 |
| Frontend assets | 6 module SCSS entries + 2 root CSS + 8 JS entries; Vite build via `vite-module-loader.js` |
| Test suite | 167 passed / 1121 assertions / ~8.6s (`php artisan test`) |
| Git | 199 commits · first `f3f4ba7` 2026-05-18 · latest `919f8e2` 2026-08-22 |
| Docs | docs/ 18 files · 9 specs · 7 plans · 9 root md/txt · 8 .tex academic reports |

### Session timeline (what shipped when)

| Date | Milestone |
|---|---|
| 05-18 → 08-15 | Core build: auth, catalog, cart/checkout/PayPal, payouts, consoles, multi-currency |
| 08-16/17 | **Modular monolith migration** (46 commits, phases 0–7, final review approved) |
| 08-18 | Profile separation, responsive pass, legal pages |
| 08-19 | 2FA→email-OTP rewrite, Google OAuth, enriched signup, password reset, contact form, sender hygiene |
| 08-20 | Coherent catalog (110 products/7 cats) + `/collection`; sweep fixes E1–E8 (status gates, FK-safe deletes, IDOR, pagination, resend, send-code buttons, partner promotion); **Email Center spec+plan** |
| 08-21 | Storage symlink fix on prod, product↔partner attribution, **EmailCenter shipped** (32 tests), deployed |
| 08-22 | Perf pass: prod disk rescued (freed 531 MB), 115 images self-hosted, lazy loading, nginx immutable cache |

---

## 4. Backend NOT Wired to the UI (verified 2026-08-22)

> **RESOLVED same day** (commits up to `7526944`, deployed): Sanctum API gates fixed +
> registration endpoint removed · dead scaffolding deleted (variant table/model, review
> CRUD stubs, orphan/scaffold controllers, legacy `user` table) · member **Edit page**
> wired (role+status) · categories/inventory `show` routes dropped · **Audit Trail**
> and **Outbound Mail** admin viewers shipped (`/admin/audit-logs`, `/admin/outbound-mail`)
> · `shop.low_stock_threshold` config added · queue-pruning scheduler registered.
> Section kept below as the original findings record.

Each item below was re-verified against current code (file:line evidence checked).

### 4.1 The Sanctum API — exists, zero consumers
- `/api/login`, `/api/register`, `/api/user` (IdentityAccess/routes/api.php), `/api/catalog`
  (CatalogDelivery), three scaffold `apiResource`s under `/api/v1/*` — **no blade/JS/test
  references any of them**. Scaffold controllers even return HTML "Hello World" views for
  JSON endpoints, and their show/create/edit target missing views (500 if visited).
- EmailCenter api.php placeholder double-prefixes to `/api/api/emailcenter` (harmless, dead).

### 4.2 API auth bypass (security-relevant)
- `AuthController::apiLogin/apiRegister` issue tokens with **NO account-status check,
  NO email verification, NO 2FA challenge** (AuthController.php:125-146, :149-165) while
  the web flows enforce all three (:84, :102, :108). Suspended/unverified accounts can
  mint API tokens today.

### 4.3 Routed-but-missing methods (guaranteed 500s)
- `admin.users.edit` (IdentityAccess/routes/web.php:61 — no `edit()` in AdminUserController)
- `admin.categories.show` (CatalogDelivery/routes/web.php:46 — no `show()`)
- `partner.inventory.show` (CatalogDelivery/routes/web.php:75 — no `show()`)

### 4.4 Dead code kept in the tree
- `ReviewController::create/edit/update` — unrouted; also point at nonexistent views
- `ProductVariant` model + `product_variants` table + unused `Product::variants()` — zero usage
- `StepUpService::invalidate()` — no callers
- Orphan scaffold controllers never routed: `IdentityAccessController`, `CatalogDeliveryController`
- `telemetrypipelines.*` web resource — linked nowhere; create/show/edit views don't exist

### 4.5 Write-only data (logged, never surfaced)
- **`audit_logs`** — written by 5 admin actions via TelemetryService; there is **no admin
  screen to view the audit trail** anywhere.
- **TelemetryPipeline `email_logs`** — written by a MessageSending listener, read by
  nobody (the visible email-history screens read EmailCenter's separate
  `email_center_logs`). Two parallel email-log tables, one orphaned.

### 4.6 Config drift
- `LowStockAlertService` reads `shop.low_stock_threshold`, which **isn't defined in
  config/shop.php** — silently falls back to hardcoded 5.

### 4.7 Nothing scheduled
- `routes/console.php` is the stock inspire stub; no scheduler runs any cleanup
  (stale carts, expired OTP rows, log rotation are all manual/nonexistent).

*(Everything else — all 12 mailables, all 11 services' main paths, commission via
`config('shop.commission_rate')`, low-stock alerts to BOTH partner and admin — is wired
and verified.)*

---

## 5. Improvement Roadmap (ranked by leverage)

### TOP 10 — do these first
1. **Guard `PaymentController::capture`** — verify order ownership before marking paid;
   wrap updates in a transaction (PaymentController.php:109-131). Money + security.
2. **Add GitHub Actions CI** — pint + `php artisan test` (~9 s suite). Near-zero cost,
   maximal credibility.
3. **Fix session fixation** — `$request->session()->regenerate()` after reset auto-login
   (PasswordResetController.php:58).
4. **Delete plaintext-password/debug logging** (AuthController.php:19,42). Three lines.
5. **Close the API bypass** — throttle + status/verification checks on `/api/login|register`.
6. **Partner fulfillment** — add `shipped` status, partner PATCH endpoint with ownership
   policy, `OrderShipped` mailable. Converts the biggest product gap into a highlight.
7. **Test the money cluster** — fake-provider PayPal tests + cancel/restock tests
   (currently ZERO tests touch PayPal or cancellation).
8. **Image variant pipeline** — thumbnail/card/full sizes on upload instead of serving
   originals (biggest remaining storefront perf win).
9. **Cache the storefront** — `Cache::remember` around home/shop/collection queries with
   invalidation on product mutations; also cap/paginate the unbounded collection page.
10. **Sentry (or Flare) + nightly DB backup schedule** — the two production-readiness
    checkboxes an examiner looks for.

### Grouped findings
- **Data integrity:** checkout/cancel already exemplary (`DB::transaction` +
  `lockForUpdate`); RESTRICT FKs deliberate + friendly-error tested; partner promotion
  still lacks a transaction (partial state possible).
- **Code health:** duplicated image handling between ProductController and
  PartnerInventoryController (extract `ProductImageService`); return types on ~12% of
  methods; N+1 on related-products (`CatalogQueryService::related` lacks eager loads);
  stale TODO/hint comments (OrderController.php:66-70, Product.php:89).
- **Testing:** ~60% of routes exercised; dark clusters = payments, payouts, fulfillment,
  review moderation, `/shop` filters, rate limiters beyond `auth`; no browser/E2E suite.
- **Performance:** search is `LIKE '%term%'` (add FULLTEXT or Scout); `inRandomOrder()`
  full-scan for related items; originals served at w=800 for card-sized slots.
- **Product gaps:** no shipped/delivered notifications, no invoice/receipt PDF, no order
  tracking timeline, no back-in-stock wishlist alerts.
- **Ops:** no CI, no error monitoring, no automated backups, no queue-failure alerting.

### Already outstanding (keep doing this)
Row-locked checkout correctness · moderation-gated reviews · CSRF hygiene everywhere ·
zero inline styles discipline · route-name immutability · friendly FK-delete UX with
tests · deep auth/2FA coverage (77 IdentityAccess test methods) · honest engineering
docs trail (specs/plans per feature).

---

## 6. Ops Runbook (pointers)

- **Deploy:** push → `ssh root@104.248.163.215 "cd /var/www/smartshop && git pull -q &&
  composer dump-autoload (COMPOSER_ALLOW_SUPERUSER=1 if new module) && php artisan
  migrate --force && php artisan route:clear -q && php artisan config:clear -q &&
  php artisan queue:restart"` (+ `npm run build` on server when assets change).
- **Queue workers:** Supervisor programs `smartshop-worker`, `atlas-worker`
  (`supervisorctl status/restart`). NEVER `pkill -f queue:work` over SSH (kills your own
  session; matches cmdline).
- **New module checklist:** module.json + composer.json + Providers (extends
  `Nwidart\Modules\Support\ModuleServiceProvider`) + scss/js asset pair + vite.config.js +
  entry in server-side modules_statuses.json (prod keeps this file server-local!) +
  COMPOSER_ALLOW_SUPERUSER=1 dump-autoload + rm bootstrap/cache/modules.php.
- **Backups:** `/root/backups/smartshop/` (db/env/nginx/commit marker, taken 2026-08-22).
- **Known server quirk:** droplet shares space with `/var/www/Atlas-Learning` (~1.2 GB,
  owner-approved leave-alone); watch `df -h` — disk hit 100% once already.

— End of report. Verify counts against `php artisan test` / `git rev-list --count HEAD`
if reading this later than 2026-08-22.
