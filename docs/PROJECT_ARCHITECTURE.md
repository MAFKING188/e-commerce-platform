# SmartShop — Project Architecture & Progress Reference

> **Purpose:** A single self-contained reference document. A new engineer should be able to read this and understand the entire project: what it is, how it is structured, what is built, what is broken, and what the roadmap is.
>
> **Generated:** 2026-08-16 · refreshed 2026-08-17 at commit `fa24575` — architecture is now a modular monolith (see §15.9); module ownership map in `MODULE_OWNERSHIP.md`.
>
> **Refreshed:** 2026-08-19 — email-OTP verification layer shipped (see §7 routes, §12.1; detailed changelog in `PROJECT_REPORT.txt` §14–15).
>
> **Companion docs:** `PROGRESS.md`, `PARTNER_ROADMAP.md`, `SESSION_HANDOVER.md`, `COLLABORATION_GUIDE.md`, `FinalSubmissionReport.tex`, `EXECUTION_LOG.tex`, `DEPLOYMENT_GUIDE.tex`.

---

## Table of Contents

1. [Project Identity](#1-project-identity)
2. [Tech Stack & Environment](#2-tech-stack--environment)
3. [High-Level Architecture](#3-high-level-architecture)
4. [Repository Layout](#4-repository-layout)
5. [Database Schema](#5-database-schema)
6. [Backend Inventory](#6-backend-inventory)
7. [Route Map](#7-route-map)
8. [Frontend Inventory & UI Wiring](#8-frontend-inventory--ui-wiring)
9. [UI ↔ Backend Wiring Matrix](#9-ui--backend-wiring-matrix)
10. [Backend NOT Wired to UI (Gaps & Orphans)](#10-backend-not-wired-to-ui)
11. [Dead / Legacy / Broken Code Inventory](#11-dead--legacy--broken-code)
12. [Progress Status (Done vs Pending)](#12-progress-status)
13. [Known Issues & Technical Debt](#13-known-issues--technical-debt)
14. [Partner / Vendor Portal Completion Analysis](#14-partner--vendor-portal-completion-analysis)
15. [Proposed Target Architecture: Modular Monolith](#15-modular-monolith-proposal)
16. [Recommendations & Next Steps](#16-recommendations--next-steps)

---

## 1. Project Identity

| | |
|---|---|
| **Product** | SmartShop — "LUWI Collection", a premium/luxury multi-vendor e-commerce ecosystem |
| **Domain** | Multi-partner (multi-vendor / "artisan") marketplace with admin command center, automated financial settlements, multi-currency, and analytics |
| **Framework** | Laravel 13.x (PHP ^8.3), blade server-rendered UI + vanilla JS (no SPA) |
| **Database** | MySQL (`e-commerce-platform`), SQLite only for tests |
| **Auth** | Session auth (web) + Laravel Sanctum (API tokens) |
| **Payments** | PayPal via `srmklive/paypal` (sandbox currently) |
| **Deployment** | DigitalOcean Droplet, Ubuntu 24.04 LTS, LEMP (Nginx + MySQL + PHP-FPM), SSL via Certbot, Supervisor queue worker — live at `smartshop-luwi.tech` |
| **Academic context** | Final project, Back-End Web Development (AY 2025–2026), built in phased sessions (Phase I conceptual → Phase IV production deployment) |
| **Audience** | 3 roles: **user** (buyer), **partner** (artisan/vendor), **admin** (platform operator) |

### Core features (as marketed by the README)

1. **Autonomous Partner Ecosystem** — partner dashboards, full inventory CRUD, logistics isolation (orders filtered per partner).
2. **Financial Command** — automated payout engine with 10% platform commission on order completion; transparent earnings for admins and partners.
3. **Intelligence & Analytics** — Chart.js 30-day revenue series (partner + admin dashboards), inventory/order/member metrics.
4. **Global Sovereignty** — session-based multi-currency (USD, EUR, GBP, MAD), custom `@money` Blade directive.
5. **Curatorial Media** — multi-image product galleries with SortableJS drag-and-drop reordering.
6. **Admin Command Center** — review moderation, member registry with pending-approval workflow, event-driven email outreach.

---

## 2. Tech Stack & Environment

**Runtime / framework**
- PHP ^8.3, Laravel framework ^13.0
- `laravel/sanctum ^4.0` (API tokens), `laravel/tinker ^3.0`, `srmklive/paypal ^3.1`
- Dev: `fakerphp/faker`, `laravel/pail`, `laravel/pint`, `mockery`, `nunomaduro/collision`, `phpunit ^12.5`

**Frontend**
- Vite 8 + laravel-vite-plugin, Tailwind CSS 4 (`resources/css/app.css`), Google Fonts (Plus Jakarta Sans / Instrument Sans)
- Chart.js (CDN), SortableJS (CDN), vanilla JS + `fetch` AJAX with CSRF — **no compiled assets in `public/build`; `resources/js/app.js` is empty; all styling is inline `<style>` in `layouts/app.blade.php`**

**Infrastructure / env (`.env`)**
- MySQL; queue = `database`; cache = `database`; session = `database` (120 min); filesystem = `local`
- Mail: Gmail SMTP (smtp.gmail.com:465, app password); PayPal sandbox credentials
- `APP_DEBUG=true` in the committed `.env`; brand string in `.env` is "Laravel" (mail subjects use "LUWI Collection" / "SmartShop")

**Composer scripts**
- `setup` — install + key:generate + migrate + npm build
- `dev` — concurrently: serve + queue:listen + pail + vite
- `test` — phpunit

---

## 3. High-Level Architecture

The app is a **modular monolith** (migration executed 2026-08-16/17, commits `2b1cc33`..`da1e36f`): five nwidart-style modules (`IdentityAccess`, `CatalogDelivery`, `MarketplacePipeline`, `PartnerHub`, `TelemetryPipeline`) each own their models, controllers, services, routes, views, migrations, seeders, tests and assets. Core keeps shared infrastructure only (layout/AppLayout component, `CurrencyService`, `Address` model, `CurrencyMiddleware`). Three "portals" share one database and one layout:

```
                    ┌────────────────────────────────────────────┐
                    │              browser (web UI)              │
                    │  blade views + inline CSS/JS + fetch AJAX  │
                    └───────────────┬────────────────────────────┘
                                    │ HTTP + CSRF + sessions
        ┌───────────────────────────┼───────────────────────────┐
        │                           │                           │
   Storefront               Admin Portal                Partner Portal
   /, /shop, /product      /admin/*                   /partner/*
   /cart /orders /profile  middleware: auth+admin     middleware: auth+partner
        │                           │                           │
        └───────────────┬───────────┴───────────┬───────────────┘
                        │                       │
               ┌────────▼────────┐     ┌────────▼────────┐
               │  Controllers     │     │  Service layer │
               │  20 controllers  │     │  CurrencyService│
               │  (role-scoped)   │     └────────────────┘
               └────────┬────────┘
                        │ Eloquent
               ┌────────▼────────┐
               │  20 models       │  ──▶ MySQL (24 tables)
               └─────────────────┘
                        │
        ┌───────────────┼───────────────────────┐
        │               │                       │
   Mailable queue  PayPal (srmklive)      Sanctum API
   (database queue)  sandbox redirects    /api/login /api/register
                                          /api/catalog /api/user
```

Key structural characteristics:

- **One web group middleware** (`CurrencyMiddleware` appended in `bootstrap/app.php`) sets session currency from `?currency=`.
- **Middleware aliases**: `admin` → `AdminMiddleware` (redirects non-admins to home), `partner` → `PartnerMiddleware`.
- **Authorization is middleware-based (role checks), NOT policy-based.** `ProductPolicy` / `ReviewPolicy` exist but `AuthServiceProvider` is not registered and no controller calls `$this->authorize()`. Policies are dead code.
- **Custom Blade directive** `@money($amount)` (registered in `AppServiceProvider`) formats via `CurrencyService`.
- **Business logic lives in controllers** (no service layer except `CurrencyService`); mailables are queued via `ShouldQueue` against the database queue (no Jobs/Events/Listeners exist).
- **Transactions used in** `OrderController@store` / `@cancel` (with `lockForUpdate()` row locks) and `PartnerController@destroy`.
- **API surface** (`routes/api.php`): login/register (public), catalog (public), `/api/user` (Sanctum). Not consumed by the web UI — intended for external/mobile clients.

---

## 4. Repository Layout

```
e-commerce-platform/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # 20 controllers (see §6.2)
│   │   ├── Middleware/           # AdminMiddleware, PartnerMiddleware, CurrencyMiddleware
│   │   └── Requests/             # Store/UpdatePartnerRequest, Store/UpdateProductRequest
│   ├── Mail/                     # 5 mailables (see §6.6)
│   ├── Models/                   # 20 models (see §6.1)
│   ├── Policies/                 # ProductPolicy, ReviewPolicy  ← DEAD (provider unregistered)
│   ├── Providers/                # AppServiceProvider (registered), AuthServiceProvider (NOT registered)
│   └── Services/                 # CurrencyService
├── bootstrap/
│   ├── app.php                   # middleware aliases, web group w/ CurrencyMiddleware, routes wiring
│   └── providers.php             # only AppServiceProvider
├── config/                       # defaults + custom: currency.php, paypal.php
├── database/
│   ├── factories/                # UserFactory only
│   ├── migrations/               # 25 migrations (see §5)
│   ├── seeders/                  # DatabaseSeeder, User, Category, Product, Review, Order
│   └── database.sqlite           # empty (tests use sqlite :memory:)
├── public/                       # index.php, .htaccess (malformed dup blocks), robots, favicon,
│                                 # storage→symlink (products/ with 3 seeded images); NO public/build
├── resources/
│   ├── components/               # LEGACY product-card (unused duplicate)
│   ├── dashboard/                # LEGACY dashboard stub (unused)
│   ├── partials/                 # LEGACY nav.blade.php (broken syntax, unused)
│   ├── users/                    # empty dir
│   ├── views/                    # ~75 blade files (see §8)
│   ├── css/app.css               # Tailwind 4 entry
│   └── js/app.js                 # EMPTY (all JS inline in blades)
├── routes/
│   ├── web.php                   # all web routes (157 lines)
│   ├── api.php                   # Sanctum API (4 endpoints)
│   └── console.php               # default `inspire` only
├── tests/
│   ├── Feature/ExampleTest.php   # GET / → 200
│   └── Unit/ExampleTest.php      # assertTrue(true)
├── docs/PROJECT_ARCHITECTURE.md  # THIS FILE
├── database_dump.sql             # STALE snapshot (pre-rebrand: vendors/vendor_products, no wishlists)
├── *.tex / *.pdf                 # academic reports: EXECUTION_LOG, FinalSubmissionReport,
│                                 # DEPLOYMENT_GUIDE, GIT_GUIDE, MASTER_DOCUMENTATION, main*.tex
├── PROGRESS.md                   # session-by-session progress log
├── PARTNER_ROADMAP.md            # partner feature roadmap w/ checkboxes
├── SESSION_HANDOVER.md           # handover notes + future recommendations
├── COLLABORATION_GUIDE.md        # audit findings + priority tasks
├── README.md                     # product overview
├── composer.json / package.json / vite.config.js / phpunit.xml
└── .env / .env.example           # NOTE: .env + database_dump.sql are committed (secret exposure)
```

---

## 5. Database Schema

**24 tables** (from migrations; `database_dump.sql` is a *stale pre-rebrand snapshot* using `vendors`/`vendor_products` and a legacy singular `user` table — do not treat it as the live schema).

### Framework tables
`users` (Laravel 11+ auth: id, name, email, email_verified_at, password, remember_token, timestamps — **this migration was later duplicated by the app's own users migration; both run**), `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens` (Sanctum).

### Application tables

| Table | Key columns | Notes / relationships |
|---|---|---|
| `users` | id, name(100), email(150, unique), password, role (enum-ish string: user/partner/admin, default 'user'), status (active/pending/suspended, added later), two_factor_type (null/'email', default null), email_verified_at (nullable, added 2026-08-19; TOTP-era `two_factor_secret` column **dropped**), timestamps | The **de-facto** users table (app migration). FK target of carts/orders/addresses/reviews/wishlists/partners |
| `categories` | id, name(100) | 1→N products |
| `products` | id, name(150), price(10,2), description, stock, image, FK category_id | 1→N product_images/variants/order_items/reviews; N→N partners via partner_products |
| `carts` | FK user_id (unique) | 1 user : 1 cart; 1→N cart_items. ⚠ down() drops `cart` (typo) |
| `cart_items` | FK cart_id, FK product_id, quantity (default 1) | |
| `orders` | FK user_id, total_price(10,2), status (pending/paid/completed/cancelled) | 1→N order_items; 1→1 payment; N→N partners via order_items→partner_products |
| `order_items` | FK order_id, FK product_id, quantity, price(10,2) | Snapshot price at purchase |
| `product_images` | FK product_id, url, position (default 0) | Gallery ordering |
| `product_variants` | FK product_id, sku, size, color, stock, price | ⚠ **Unused** — `ProductVariant` model is empty; no UI |
| `reviews` | FK user_id, FK product_id, rating, comment, status (pending/approved/rejected, default approved, added later) | Moderation workflow |
| `payments` | FK order_id, method, status, transaction_id, amount(10,2) | ⚠ `provider` column migration is a **no-op** (never applied) |
| `addresses` | FK user_id, line1, line2, city, state, zip, country, is_primary (added later) | 1 user : N addresses |
| `partners` | id, name, description (text), website, contact_info, FK user_id (nullable, added later) | Renamed from `vendors`; 1 partner : 1 user |
| `partner_products` | FK partner_id, FK product_id | Pivot, renamed from `vendor_products` (column vendor_id→partner_id) |
| `wishlists` | FK user_id (cascade), FK product_id (cascade), unique(user_id, product_id) | Added June 11 |
| `payouts` | FK partner_id (cascade), FK order_id (cascade), amount(12,2), status (pending/processed), transaction_reference, processed_at | Settlement engine; created per partner when an admin completes a paid order |

### Migrations quirks (do not "fix" casually — noted for modular refactor)
1. `2026_05_15_070331_add_provider_to_payments_table` — **empty no-op**; `Payment::$fillable` includes `provider` but the column doesn't exist.
2. `carts` down() drops `cart` (wrong table).
3. Two migrations share timestamp `2026_06_13_104753`.
4. App's own `create_users_table` duplicates the framework default.

---

## 6. Backend Inventory

### 6.1 Models (20)

| Model | Table | Purpose / key relations |
|---|---|---|
| `User` | users | Authenticatable + Sanctum; role, status, `two_factor_type`, `email_verified_at`, `isPartner()` helper; orders, cart, addresses, reviews, wishlists |
| `Product` | products | getImageUrlAttribute() w/ Unsplash fallback; isWishlistedByUser(); category, cartItem, orderItems, images, variants, reviews, partners (pivot) |
| `Order` | orders | user, items, payment, payouts |
| `Partner` | partners | user, products (pivot), payouts, orders (custom belongsToMany through order_items + partner_products) |
| `Category` | categories | products |
| `Cart` / `CartItem` | carts / cart_items | user↔cart 1:1; cart→items 1:N |
| `OrderItem` | order_items | order, product |
| `Payment` | payments | order |
| `Payout` | payouts | partner, order |
| `Address` | addresses | user |
| `PartnerProduct` | partner_products | partner, product (pivot model) |
| `ProductImage` | product_images | *(no relations)* |
| `ProductVariant` | product_variants | **Empty class** — dead |
| `Review` | reviews | user, product; status lifecycle |
| `Wishlist` | wishlists | user, product |

⚠ **`User` has NO `wishlist()` / `wishlists()` relationship defined** — `WishlistController` calls `Auth::user()->wishlist()` (non-existent) — see §11.

### 6.2 Controllers (20)

**Base:** `Controller` (abstract).

**Public / catalog:**
| Controller | Methods | Notes |
|---|---|---|
| `ViewController` | home, shop, product, about, contact, partnerProfile | shop: search/category/price filters + sort + paginate(12); product: only approved reviews; partnerProfile: public artisan page |

**Auth / identity:**
| Controller | Methods | Notes |
|---|---|---|
| `AuthController` | register, login, logout, apiRegister, apiLogin, verifyEmailPage, verifyEmail, resendVerifyEmail | Role-based status (user→active; partner/admin→pending); queues WelcomeMember; login blocks non-active; **challenge-before-login**: admins/partners (+ any user with email codes enabled) get a `2fa.pending` session and are redirected to `/2fa/challenge` before `Auth::login` runs; unverified new signups are bounced to `/verify-email`; API returns Sanctum tokens |
| `TwoFactorController` | challenge, verify, resend, enableEmail, confirm, disable | Email-OTP only (TOTP removed 2026-08-19). challenge/verify/resend guarded by `2fa.pending`; enable/confirm/disable on settings, throttled (`2fa`, `2fa-resend`, `2fa-enroll` limiters) |
| `PasswordResetController` | showForgotForm, sendResetLink, showResetForm, storeNewPassword | sendResetLink queues `PasswordResetMail` w/ broker plaintext token (DB stores bcrypt hash); reset logs in + queues `PasswordChangedMail`; forgot/reset throttled (5/min) |
| `GoogleAuthController` | redirectToGoogle, handleCallback | OAuth sign-in; new users created `email_verified_at = now()`; 2FA enabled users fall through the same challenge |
| `UserController` | index, show, edit, updateProfile, update, destroy, updatePassword, updateAvatar, security, settings | show = self profile w/ orders+addresses; updateProfile updates name/email + primary address — **email change requires a step-up email code**; updatePassword requires step-up code; update/destroy = admin role mgmt (update role in:user,admin only) |
| `AdminUserController` | index, update, approve, destroy | Member registry: search+status filter; emails UserStatusUpdated on role/status change; blocks self-delete |
| `WishlistController` | index, toggle | ⚠ **BROKEN — see §11** |

**Commerce:**
| Controller | Methods | Notes |
|---|---|---|
| `CartController` | index, add, remove | add validates stock incl. existing qty |
| `OrderController` | index, store, cancel | store: transaction, lockForUpdate on products, price snapshot, stock decrement, cart clear, OrderConfirmed mail; cancel: restore stock, void pending payments, OrderCancelled mail |
| `PaymentController` | store, capture | PayPal CAPTURE flow via srmklive; saves pending Payment; capture marks paid + PaymentSuccess mail |

**Admin:**
| Controller | Methods | Notes |
|---|---|---|
| `AdminDashboardController` | index | Stats: revenue, active orders, catalog size, members, low stock (<5), pending reviews, pending users, 5 recent orders |
| `AdminOrderController` | index, show, complete | complete: paid→completed + **creates Payout per partner at 90% net (10% commission)** |
| `AdminPayoutController` | index, process | process: pending→processed + transaction_reference + processed_at |
| `ProductController` | index, create, store, edit, update, destroy, deleteImage, reorderImages | Admin catalog CRUD; multi-image upload to storage/products; JSON image ops |
| `CategoryController` | CRUD | Admin categories |
| `PartnerController` | index, create, store, show, edit, update, destroy, addProduct, removeProduct | Admin-managed partners; destroy detaches pivot first (transaction) |
| `ReviewController` | index, create, store, edit, update, approve, reject, destroy | ⚠ **Only index/approve/reject/destroy are routed** — create/store/edit/update are dead methods (no customer review submission anywhere) |

**Partner:**
| Controller | Methods | Notes |
|---|---|---|
| `PartnerDashboardController` | index | Inventory count, revenue/items sold, pending payout, recent orders, 30-day daily sales chart data |
| `PartnerInventoryController` | index, bulkAction, create, store, edit, update, destroy, deleteImage, reorderImages | getPartner() by auth user_id; creates product + attaches to partner + images |
| `PartnerOrderController` | index, show | Orders containing partner's products, via Partner::orders() |
| `PartnerPayoutController` | index | Payout list + processed/pending totals |

### 6.3 Middleware (4) + rate limiters
- `AdminMiddleware` (alias `admin`) — redirect non-admin → home w/ error
- `PartnerMiddleware` (alias `partner`) — redirect non-partner → home w/ error
- `CurrencyMiddleware` — appended to `web` group; sets `currency` session from `?currency=` param validated against `config/currency.php`
- `Ensure2faChallenge` (alias `2fa.pending`) — redirects to `/login` unless `2fa.pending` session flag is set (guards challenge routes). TOTP-era `Ensure2faEnrolled` **deleted** (2026-08-19)
- Rate limiters (`RateLimiter::for` in TelemetryPipeline `RouteServiceProvider`): `auth` (5/min login+register), `checkout` (3/min), `2fa` (5/min verify), `2fa-resend` (5/min), `2fa-enroll` (5/min), `2fa-verify` (5/min signup email verify), `forgot-password` via `throttle:5,1`

### 6.4 FormRequests (4)
- `StorePartnerRequest` / `UpdatePartnerRequest` — admin-only; name unique (ignore-self on update), description, contact_info, website, user_id
- `StoreProductRequest` / `UpdateProductRequest` — admin **or partner**; name, price, category_id, stock, description, single `image` or `images[]` (jpeg/png/jpg/gif ≤ 2MB)

### 6.5 Services (3)
- `CurrencyService` — static `convert($amount)` (session currency × rate), `format($amount)` (symbol + 2 decimals), `getCurrent()`. Rates hardcoded in `config/currency.php`.
- `OtpService` — 6-digit email OTP: `issue($user)` (returns plaintext, bcrypt-hashed in cache `2fa:otp:{id}`, TTL 600s, single-use), `check($user, $code)`, `send($user)`. Queues `OtpMail`.
- `StepUpService` — step-up marker for sensitive buyer actions (checkout, password/email change, 2FA disable): `begin($user)` (15-min `stepup.verified` session flag), `isVerified`, `complete`, `invalidate`.

### 6.6 Mailables (9) — 8 queued (ShouldQueue)
- `WelcomeMember` (queued) — markdown `emails.members.welcome`
- `OrderConfirmed` (queued) — markdown `emails.orders.confirmed` (⚠ template has stray trailing `tml>`)
- `OrderCancelled` (queued) — markdown `emails.orders.cancelled`
- `PaymentSuccess` (queued) — markdown `emails.payments.success`
- `UserStatusUpdated` (NOT queued) — plain view `emails.user_status_updated`
- `OtpMail` (queued) — markdown `emails.otp`; generic "Your LUWI verification code" (login challenge, signup verify, step-up)
- `PasswordResetMail` (queued) — markdown `emails.password.reset`; link = `url('/reset-password/{token}')` (plaintext token)
- `PasswordChangedMail` (queued) — markdown `emails.password.changed`
- `ContactMessageMail` (queued) — markdown `emails.contact-message`; to `config('shop.contact_email')`, `Reply-To` = submitter

⚠ **Local dev:** `QUEUE_CONNECTION=sync` — no worker runs locally; `database` queue silently stalls all mail (see `PROJECT_REPORT.txt` §15).

### 6.7 Providers / Policies
- `AppServiceProvider` — registered; registers `@money` directive
- `AuthServiceProvider` — **NOT registered**; policy map commented out → both policies are dead code

### 6.8 What does NOT exist (important)
No `Events/`, `Listeners/`, `Jobs/`, `Notifications/`, `Console/Commands/`, `Exceptions/`, `Support/`, `Helpers/` directories. No scheduled tasks. No custom console commands.

---

## 7. Route Map

### 7.1 Web routes — Public (no middleware)

| Method | URI | Handler | Name |
|---|---|---|---|
| GET | `/` | ViewController@home | `home` |
| GET | `/shop` | ViewController@shop | `shop` |
| GET | `/product/{id}` | ViewController@product | `product.show` |
| GET | `/artisan-profile/{id}` | ViewController@partnerProfile | `partner.profile` |
| GET | `/about` | ViewController@about | `about` |
| GET | `/contact` | ViewController@contact | `contact` |
| POST | `/contact` | ContactController@store | `contact.store` (throttle:5,1 — persists to `contact_messages` + queues `ContactMessageMail`) |
| GET | `/login` | closure → auth.login | `login` (guest) |
| GET | `/signup` | closure → auth.signup | `signup` (guest) |
| GET | `/forgot-password` | PasswordResetController@showForgotForm | `forgot-password` (guest) |
| POST | `/forgot-password` | PasswordResetController@sendResetLink | `password.email` (guest, throttle:5,1) |
| GET | `/reset-password/{token}` | PasswordResetController@showResetForm | `password.reset` (guest) |
| POST | `/reset-password` | PasswordResetController@storeNewPassword | `password.store` (guest) |
| GET | `/auth/google/redirect` | GoogleAuthController@redirectToGoogle | `auth.google.redirect` |
| GET | `/auth/google/callback` | GoogleAuthController@handleCallback | `auth.google.callback` |
| POST | `/createaccount` | AuthController@register | — (throttle:auth) |
| POST | `/accessaccount` | AuthController@login | — (throttle:auth) |
| POST | `/logout` | AuthController@logout | `logout` |
| GET | `/verify-email` | AuthController@verifyEmailPage | `verify-email` (no middleware; redirects if no pending) |
| POST | `/verify-email` | AuthController@verifyEmail | `verify-email.post` (throttle:2fa-verify) |
| POST | `/verify-email/resend` | AuthController@resendVerifyEmail | `verify-email.resend` (throttle:2fa-resend) |

**2FA challenge (`2fa.pending` middleware — before full login):**
| Method | URI | Handler | Name |
|---|---|---|---|
| GET | `/2fa/challenge` | TwoFactorController@challenge | `2fa.challenge` |
| POST | `/2fa/challenge/verify` | TwoFactorController@verify | `2fa.verify` (throttle:2fa) |
| POST | `/2fa/challenge/resend` | TwoFactorController@resend | `2fa.resend` (throttle:2fa-resend) |

### 7.2 Web routes — Authenticated member (`auth`)

| Method | URI | Handler | Name |
|---|---|---|---|
| GET | `/cart` | CartController@index | `cart.index` |
| POST | `/cart/add` | CartController@add | `cart.add` |
| DELETE | `/cart/remove/{id}` | CartController@remove | `cart.remove` |
| GET | `/orders` | OrderController@index | `orders.index` |
| POST | `/orders/store` | OrderController@store | `orders.store` |
| PATCH | `/orders/{id}/cancel` | OrderController@cancel | `orders.cancel` |
| POST | `/paypal/store` | PaymentController@store | `paypal.store` |
| GET | `/paypal/capture` | PaymentController@capture | `paypal.capture` |
| GET | `/paypal/cancel` | closure (redirect w/ error) | `paypal.cancel` |
| GET | `/profile` | UserController@show | `profile` |
| PUT | `/profile/update` | UserController@updateProfile | `profile.update` (step-up code required when email changes) |
| POST | `/profile/avatar` | UserController@updateAvatar | `profile.avatar` |
| PUT | `/profile/password` | UserController@updatePassword | `profile.password` (step-up code required) |
| GET | `/profile/security` | UserController@security | `profile.security` |
| GET | `/profile/settings` | UserController@settings | `profile.settings` |
| POST | `/profile/settings/twofa/enable-email` | TwoFactorController@enableEmail | `profile.settings.twofa.enable-email` (throttle:2fa-enroll) |
| POST | `/profile/settings/twofa/confirm` | TwoFactorController@confirm | `profile.settings.twofa.confirm` (throttle:2fa-enroll) |
| POST | `/profile/settings/twofa/disable` | TwoFactorController@disable | `profile.settings.twofa.disable` (throttle:2fa-enroll; password + step-up code required) |
| GET | `/archive` | WishlistController@index | `profile.wishlist` |
| POST | `/wishlist/toggle` | WishlistController@toggle | `wishlist.toggle` |

### 7.3 Web routes — Admin (`auth`+`admin`, prefix `admin/`, prefix name `admin.`)

| Method | URI | Handler | Name |
|---|---|---|---|
| GET | `/admin/dashboard` | AdminDashboardController@index | `admin.dashboard` |
| GET | `/admin/orders` | AdminOrderController@index | `admin.orders.index` |
| GET | `/admin/orders/{id}` | AdminOrderController@show | `admin.orders.show` |
| POST | `/admin/orders/{id}/complete` | AdminOrderController@complete | `admin.orders.complete` |
| GET | `/admin/products` | ProductController@index | `admin.products.index` |
| GET | `/admin/products/create` | ProductController@create | `admin.products.create` |
| POST | `/admin/products` | ProductController@store | `admin.products.store` |
| GET | `/admin/products/{product}` | ProductController@show | `admin.products.show` ⚠ no show() method → 404 |
| GET | `/admin/products/{product}/edit` | ProductController@edit | `admin.products.edit` |
| PUT/PATCH | `/admin/products/{product}` | ProductController@update | `admin.products.update` |
| DELETE | `/admin/products/{product}` | ProductController@destroy | `admin.products.destroy` |
| POST | `/admin/products/{product}/reorder-images` | ProductController@reorderImages | `admin.products.reorder-images` |
| DELETE | `/admin/products/{product}/images/{image}` | ProductController@deleteImage | `admin.products.delete-image` |
| GET | `/admin/users` | AdminUserController@index | `admin.users.index` |
| GET | `/admin/users/{id}/edit` | AdminUserController@edit | `admin.users.edit` |
| PUT/PATCH | `/admin/users/{id}` | AdminUserController@update | `admin.users.update` |
| DELETE | `/admin/users/{id}` | AdminUserController@destroy | `admin.users.destroy` |
| POST | `/admin/users/{id}/approve` | AdminUserController@approve | `admin.users.approve` |
| GET | `/admin/categories` | CategoryController@index | `admin.categories.index` |
| GET | `/admin/categories/create` | CategoryController@create | `admin.categories.create` |
| POST | `/admin/categories` | CategoryController@store | `admin.categories.store` |
| GET | `/admin/categories/{category}/edit` | CategoryController@edit | `admin.categories.edit` |
| PUT/PATCH | `/admin/categories/{category}` | CategoryController@update | `admin.categories.update` |
| DELETE | `/admin/categories/{category}` | CategoryController@destroy | `admin.categories.destroy` |
| GET | `/admin/partners` | PartnerController@index | `admin.partners.index` |
| GET | `/admin/partners/create` | PartnerController@create | `admin.partners.create` |
| POST | `/admin/partners` | PartnerController@store | `admin.partners.store` |
| GET | `/admin/partners/{partner}` | PartnerController@show | `admin.partners.show` |
| GET | `/admin/partners/{partner}/edit` | PartnerController@edit | `admin.partners.edit` |
| PUT/PATCH | `/admin/partners/{partner}` | PartnerController@update | `admin.partners.update` |
| DELETE | `/admin/partners/{partner}` | PartnerController@destroy | `admin.partners.destroy` |
| POST | `/admin/partners/{id}/add-product` | PartnerController@addProduct | `admin.partners.add_product` |
| DELETE | `/admin/partners/{id}/remove-product/{productId}` | PartnerController@removeProduct | `admin.partners.remove_product` |
| GET | `/admin/reviews` | ReviewController@index | `admin.reviews.index` |
| POST | `/admin/reviews/{id}/approve` | ReviewController@approve | `admin.reviews.approve` |
| POST | `/admin/reviews/{id}/reject` | ReviewController@reject | `admin.reviews.reject` |
| DELETE | `/admin/reviews/{id}` | ReviewController@destroy | `admin.reviews.destroy` |
| GET | `/admin/payouts` | AdminPayoutController@index | `admin.payouts.index` |
| POST | `/admin/payouts/{id}/process` | AdminPayoutController@process | `admin.payouts.process` |

### 7.4 Web routes — Partner (`auth`+`partner`, prefix `partner/`, prefix name `partner.`)

| Method | URI | Handler | Name |
|---|---|---|---|
| GET | `/partner/dashboard` | PartnerDashboardController@index | `partner.dashboard` |
| GET | `/partner/inventory` | PartnerInventoryController@index | `partner.inventory.index` |
| GET | `/partner/inventory/create` | PartnerInventoryController@create | `partner.inventory.create` |
| POST | `/partner/inventory` | PartnerInventoryController@store | `partner.inventory.store` |
| GET | `/partner/inventory/{product}/edit` | PartnerInventoryController@edit | `partner.inventory.edit` |
| PUT/PATCH | `/partner/inventory/{product}` | PartnerInventoryController@update | `partner.inventory.update` |
| DELETE | `/partner/inventory/{product}` | PartnerInventoryController@destroy | `partner.inventory.destroy` |
| POST | `/partner/inventory/bulk-action` | PartnerInventoryController@bulkAction | `partner.inventory.bulk-action` |
| POST | `/partner/inventory/{product}/reorder-images` | PartnerInventoryController@reorderImages | `partner.inventory.reorder-images` |
| DELETE | `/partner/inventory/{product}/images/{image}` | PartnerInventoryController@deleteImage | `partner.inventory.delete-image` |
| GET | `/partner/orders` | PartnerOrderController@index | `partner.orders.index` |
| GET | `/partner/orders/{order}` | PartnerOrderController@show | `partner.orders.show` |
| GET | `/partner/payouts` | PartnerPayoutController@index | `partner.payouts.index` |

### 7.5 API routes (`routes/api.php`)

| Method | URI | Auth | Purpose |
|---|---|---|---|
| POST | `/api/login` | none | Sanctum token login |
| POST | `/api/register` | none | Sanctum token register |
| GET | `/api/catalog` | none | Paginated products + category + images (15/page) |
| GET | `/api/user` | `auth:sanctum` | Current user |

---

## 8. Frontend Inventory & UI Wiring

**All styling is inline** in `layouts/app.blade.php` (dark/light theme design system). `resources/js/app.js` is empty. CDN libs: Chart.js (partner dashboard), SortableJS (admin + partner inventory edit).

### 8.1 Layouts & shared partials

| File | Purpose |
|---|---|
| `layouts/app.blade.php` | Main layout: nav (Discovery/Collection/Story/Support), user dropdown (profile, archive, orders, cart, admin/partner dashboards, logout), currency switcher (GET `?currency=`), footer, toast system, theme toggle, mobile menu, `toggleWishlist()` AJAX |
| `partials/admin-nav.blade.php` | Admin nav: Dashboard, Fulfillment, Inventory, Members, Supply Chain, Financials, Community, Categories |
| `partials/partner-nav.blade.php` | Partner nav: Dashboard, Orders, Inventory, Earnings |
| `partials/pagination.blade.php` | Custom "luxury" pagination (shop, admin products, users) |

### 8.2 Storefront views

| File | Route rendered by | Wiring |
|---|---|---|
| `home.blade.php` | `home` | Hero, Editor's Choice (4), Latest Drop; links shop/signup |
| `shop.blade.php` | `shop` | Filter drawer GET → shop (search, category, min/max price, sort), product cards, pagination |
| `product.blade.php` | `product.show` | Gallery + thumbnails, add-to-bag POST → `cart.add`, reviews section (⚠ no submit form), related products |
| `partner_profile.blade.php` | `partner.profile` | Public artisan page: name/description/website + products |
| `wishlist.blade.php` | `profile.wishlist` | "Your Archive" via product cards (⚠ backed by broken controller) |
| `about.blade.php`, `contact.blade.php` | about / contact | contact form wired: POST `/contact` → `contact_messages` + queued mail + admin list (`admin/contacts`) |
| `components/product-card.blade.php` | — (component) | Card with `@money`, partner attribution, wishlist heart → `toggleWishlist()` |

### 8.3 Auth views

| File | Wiring |
|---|---|
| `auth/login.blade.php` | POST `/accessaccount` |
| `auth/signup.blade.php` | POST `/createaccount` (name, email, password, role select user/partner/admin) |

### 8.4 Member views

| File | Wiring |
|---|---|
| `cart/index.blade.php` | Per-item DELETE `cart.remove`; checkout POST `orders.store` |
| `orders/index.blade.php` | PATCH `orders.cancel`; PayPal form POST `paypal.store` (hidden order_id); PayPal donation CTA |
| `users/show.blade.php` | `/profile` — PUT `profile.update` + order history |

### 8.5 Admin views

| File | Wiring |
|---|---|
| `admin/dashboard.blade.php` | Quick actions; recent orders table |
| `admin/orders/index.blade.php` | "Mark Shipped" POST `admin.orders.complete` (paid→completed) |
| `admin/orders/show.blade.php` | Detail; back link |
| `admin/products/*` → actually `products/index, create, edit` (view `products/show.blade.php` exists but route 404s) | store/update/destroy; SortableJS reorder + fetch image delete |
| `admin/users/index.blade.php` | Status filter GET; role select auto-PUT `admin.users.update`; POST `approve`; DELETE `destroy` |
| `admin/categories/*` | store/update/destroy |
| `admin/partners/index, create, edit, show` | store/update/destroy; show has "Map New Piece" POST `add_product` + "Sever" DELETE `remove_product` |
| `admin/reviews/index.blade.php` | POST approve/reject; DELETE destroy |
| `admin/payouts/index.blade.php` | POST `admin.payouts.process` w/ transaction_reference |

### 8.6 Partner views (Artisan Portal)

| File | Wiring |
|---|---|
| `partner/dashboard.blade.php` | Chart.js 30-day revenue line chart; stat cards; recent orders |
| `partner/inventory/index.blade.php` | Bulk-action POST `partner.inventory.bulk-action` (delete); checkboxes + select-all |
| `partner/inventory/create.blade.php` | POST `partner.inventory.store` (multipart, images[]) |
| `partner/inventory/edit.blade.php` | PUT `partner.inventory.update`; SortableJS reorder + fetch delete images; DELETE destroy |
| `partner/orders/index.blade.php` | Order list; links to show |
| `partner/orders/show.blade.php` | ⚠ **UNCOMMITTED in working tree** — fulfillment detail filtered by partner |
| `partner/payouts/index.blade.php` | Earnings stats + payout table |
| `partner/pagination/*` (9 files) | Vendor Laravel paginator templates (unused overrides) |

### 8.7 Email templates (8)
`emails/members/welcome`, `emails/orders/confirmed` (raw HTML, stray `tml>` typo), `emails/orders/cancelled`, `emails/payments/success`, `emails/user_status_updated`, `emails/otp` (markdown), `emails/password/reset` (markdown), `emails/password/changed` (markdown).

### 8.8 AJAX / fetch endpoints used by UI
1. `layouts/app.blade.php` → **POST `/wishlist/toggle`** (JSON `{product_id}`) — wishlist hearts (⚠ backend broken)
2. `products/edit.blade.php` → POST `/admin/products/{id}/reorder-images` + DELETE `/admin/products/{id}/images/{imageId}` (SortableJS)
3. `partner/inventory/edit.blade.php` → POST `/partner/inventory/{id}/reorder-images` + DELETE `/partner/inventory/{id}/images/{imageId}` (SortableJS)

---

## 9. UI ↔ Backend Wiring Matrix

Every rendered page and the backend it exercises:

| Portal / page | Backend (route → controller) | Status |
|---|---|---|
| Home, Shop, Product, About, Contact, Artisan profile | ViewController (home/shop/product/about/contact/partnerProfile) | ✅ wired |
| Login / Signup | AuthController (login/register) | ✅ wired |
| Cart | CartController (index/add/remove) | ✅ wired |
| Checkout → PayPal | OrderController@store → PaymentController (store/capture/cancel) | ✅ wired (PayPal redirect) |
| Orders history | OrderController (index/cancel) | ✅ wired |
| Profile | UserController (show/updateProfile) | ✅ wired |
| Archive (wishlist) | WishlistController@index | ⚠ **wired but BROKEN** |
| Wishlist hearts (all cards) | WishlistController@toggle | ⚠ **wired but BROKEN** |
| Admin dashboard | AdminDashboardController@index | ✅ wired |
| Admin orders | AdminOrderController (index/show/complete) | ✅ wired |
| Admin products + media | ProductController (CRUD + image ops) | ✅ wired (except `show` route 404) |
| Admin categories | CategoryController | ✅ wired |
| Admin members | AdminUserController (index/update/approve/destroy) | ✅ wired |
| Admin partners | PartnerController (CRUD + add/remove product) | ✅ wired |
| Admin reviews moderation | ReviewController (index/approve/reject/destroy) | ✅ wired |
| Admin payouts | AdminPayoutController (index/process) | ✅ wired |
| Partner dashboard | PartnerDashboardController@index | ✅ wired |
| Partner inventory | PartnerInventoryController | ✅ wired |
| Partner orders | PartnerOrderController (index/show) | ✅ wired |
| Partner payouts | PartnerPayoutController@index | ✅ wired |
| Emails | 5 mailables | ✅ wired |
| API endpoints | AuthController apiLogin/apiRegister, closures | ⚠ **no UI consumer** (intentional API) |

---

## 10. Backend NOT Wired to UI (Gaps & Orphans)

> Status as of 2026-08-17: the two 🔴 Critical items (wishlist, reviews) are **fixed** — wishlist toggle/archive works end-to-end (`WishlistTest` green) and customer review submission is live (`ReviewSubmissionTest` green). Partner self-edit of the public artisan profile is live (`partner.profile.edit/update`).

### 🟠 Medium
| # | Gap | Details |
|---|---|---|
| 1 | **API surface has no consumer/docs/tests** | `/api/login`, `/api/register`, `/api/catalog`, `/api/user` are registered but nothing in the UI uses them; no API tests; no docs. Fine as a design decision, but currently unverifiable. |
| 2 | ~~Contact form dummy~~ **FIXED 2026-08-19** | `contact.blade.php` → POST `/contact`; messages persisted (`contact_messages`), queued to `config('shop.contact_email')` w/ reply-to submitter, admin list at `admin/contacts` |

### 🟡 Low
| # | Gap | Details |
|---|---|---|
| 3 | `product_variants` table + `ProductVariant` model + migration fully unused (kept for future sizing/color variants) | |
| 4 | No way for a partner to see *their* reviews breakdown | |
| 5 | Admin product `show` route intentionally removed (`Route::resource(...)->except(['show'])`) — no admin product-detail page | |

---

## 11. Dead / Legacy / Broken Code

> Status as of 2026-08-17: the full §11 inventory was purged in Task 7.1 (commit `cea2f55`, 20 files) — legacy `resources/views` duplicates, orphaned `admin.blade.php`, broken `partials/nav.blade.php`, empty `app.js` stub, `database_dump.sql`, malformed `.htaccess`, the no-op `add_provider_to_payments` migration, and the payment `provider` fillable entry. What remains is deliberate:

| Item | Location | State |
|---|---|---|
| `AuthServiceProvider` | app/Providers | Not registered; policies commented out |
| `ProductVariant` model + `product_variants` table | Modules/CatalogDelivery | Unused by design (future variant support) |
| `emails/orders/confirmed.blade.php` stray `tml>` typo | Modules/MarketplacePipeline | Historical; harmless |
| `AuthController@apiRegister/apiLogin` | Modules/IdentityAccess | Live but undocumented API surface |
| ~~`contact.blade.php` dummy~~ | Modules/CatalogDelivery | **fixed 2026-08-19** (see §10) |

---

## 12. Progress Status

### 12.1 Reported DONE (verified 2026-08-17)

- ✅ Phase I–IV: design → implementation → hardening → production deployment (DigitalOcean droplet, LEMP, SSL, Supervisor queue worker)
- ✅ Production hardening: FormRequests, `lockForUpdate()` transactional checkout, route refactor, CSRF, `$fillable`, OpenGraph/SEO meta
- ✅ Role-based registration with pending-approval workflow (user active; partner/admin pending → `admin.users.approve`)
- ✅ Member registry with keyword search + status filtering; consolidated admin-nav
- ✅ Partner ecosystem: dashboard w/ revenue metrics, inventory CRUD + bulk delete, multi-image media manager + SortableJS, order fulfillment isolation, payout engine w/ 10% commission, Chart.js 30-day analytics, status mailers
- ✅ **Production-grade partner console** (2026-08-17): `.pc-*` component layer, segmented nav, filters, confirm dialogs, empty states, dark mode, responsive (commit `fa24575`)
- ✅ Multi-currency (USD/EUR/GBP/MAD) via `CurrencyMiddleware` + `@money`
- ✅ Sanctum API (login/register/catalog/user)
- ✅ Database-backed wishlist + AJAX hearts (**controller finished and tested** — `WishlistTest` green)
- ✅ Customer review submission (`ReviewSubmissionTest` green)
- ✅ Partner self-edit of public artisan profile (`partner.profile.edit/update`)
- ✅ Wishlist / multi-partner payout split (equal split, tested — `PayoutSplitTest` green)
- ✅ Business rules in `config/shop.php` (commission rate, default currency)
- ✅ Rate limiting on auth (5/min) and checkout (3/min) via `RateLimiter::for` in TelemetryPipeline
- ✅ **Modular monolith migration executed** (commits `2b1cc33`..`da1e36f`; see §15.9)
- ✅ **Email-OTP verification layer** (2026-08-19, commits `3ba967e`..`3b431e4`): mandatory email code challenge before login for admins + partners; buyer opt-in email codes; step-up codes at checkout, password/email change, 2FA disable; mandatory signup email verification; TOTP removed; `Ensure2faEnrolled` deleted; all five admin route groups gated (bug-fix `da3688e`). Full suite **106 tests / 360 assertions** green; deployed + live-verified on smartshop-luwi.tech (spec/plan in `docs/superpowers/`)
- ✅ **Coherent marketplace catalog + /collection page** (2026-08-20, commit `fa71ce1`): `/collection` editorial page (hero + New Arrivals + Featured) with nav/footer links; 8 faker categories removed; Beauty & Wellness, Sports & Outdoors, Toys & Games added (15 products each); every product renamed to a unique coherent name (zero duplicates; live DB 65→110 products); `CatalogInventory` seeder class is the single source of truth for `ProductSeeder` + live-DB migration `2026_08_19_170001_coherent_catalog`; no `href="#"` remains. Full suite **113 tests / 401 assertions** green; deployed + live-verified.

### 12.2 Reported PENDING / TODO

- **PARTNER_ROADMAP Section D — "Profile & Trust Hardening"**: `profiles` table, GDPR/CCPA consent timestamps, Partner KYC documents, Admin Audit Log (audit/email log tables exist via TelemetryPipeline but no UI)
- **SESSION_HANDOVER future recommendations**: low-stock email alerts (service exists), partner onboarding guide, Stripe Connect automated disbursement, global shipping APIs
- **Contact form** backend (see §10 #2)
- **API tests / docs** (see §10 #1)

### 12.3 Git state
- Branch `main`, working tree clean. Modular monolith migration complete (phases 0–7, see §15.9): all models/controllers/services/views/routes/migrations/seeders/tests live in `Modules/`; module ownership map in `MODULE_OWNERSHIP.md` (verified against filesystem).
- **Uncommitted working tree:** none.

---

## 13. Known Issues & Technical Debt

| Severity | Issue |
|---|---|
| 🔴 Security | `.env` (Gmail app password, PayPal sandbox keys) is committed to git — rotate credentials and gitignore it |
| 🟠 Security | Policies dead → authorization is middleware-role-only; `ProductController` (admin-only by route placement) has no ownership checks for partner-created products |
| 🟠 Stability | Contact form dummy (`action="#"`); `carts` down() typo (historical, migrate:fresh clean) |
| 🟠 Ops | Local file storage (needs S3/Cloudinary); no async failure logging; `APP_DEBUG=true` |
| 🟡 Code | Business logic still heavy in controllers (service layer only for payout/checkout/low-stock/currency); partner console filters validated in-controller; duplicate `pending/paid/completed/cancelled` status strings across modules (no shared enum) |
| 🟡 Tests | 113 tests / 401 assertions (IdentityAccess: 2FA/OTP, signup verification, profiles, resets; MarketplacePipeline: checkout w/ step-up, payouts, wishlist, reviews; CatalogDelivery: contact form, collection page, catalog coherence) — no API tests, no rate-limiter test |

---

## 14. Partner / Vendor Portal Completion Analysis

### 14.1 What exists (functional today)
- **Partner dashboard** — inventory count, revenue, items sold, pending payout, recent orders, Chart.js 30-day sales series
- **Inventory** — full CRUD, multi-image galleries (SortableJS), bulk delete
- **Orders** — filtered fulfillment view (`partner/orders/show.blade.php`, currently **uncommitted**)
- **Earnings** — payout list + processed/pending totals
- **Public artisan profile** — `/artisan-profile/{id}` with partner bio/website + product grid
- **Account lifecycle** — partner signup → pending → admin approve → active; status-change email

### 14.2 What is missing / incomplete
1. **Self-service partner profile** — partners cannot edit their public artisan profile (bio, website, logo, banner); admin-only today (PR-2 gap)
2. **Section D "Profile & Trust Hardening"** — profiles table, GDPR/CCPA consent, KYC documents, audit log
3. **Payout revenue split fix** — multi-partner orders overpay partners (financial leak)
4. **Partner-facing review insights** — no per-partner product ratings/reviews view
5. **Onboarding** — no partner onboarding guide/flow (SESSION_HANDOVER rec.)
6. **Low-stock alerts** — no email alert for partner inventory (SESSION_HANDOVER rec.)
7. **Partner order status transitions** — partner can view orders but cannot update fulfillment status (e.g., shipped) — logistics isolation is read-only today
8. **Commission/config** — 10% hardcoded in `AdminOrderController@complete`; should be config-driven
9. Wishlist broken affects storefront (not partner-specific, but blocks "my favorites" UX)

### 14.3 Suggested completion sequence (see also §16)
Phase A (bugs first): fix WishlistController, add review submission, fix payout split, fix `products.show` 404.
Phase B (partner self-service): partner profile edit (bio/website/logo), fulfillment status updates by partner, low-stock alert mailer, config-driven commission.
Phase C (Section D trust): profiles table, KYC, GDPR consent timestamps, audit log.

---

## 15. Modular Monolith Proposal

> Analysis requested by the project owner: migrate from the current role-organized monolith to a **modular monolith** to support scaling to many users and many partners. This section is the design analysis — nothing has been changed in the codebase.

> **STATUS (Task 7.3):** migration **complete** — phases 0–7 all done. Five modules (IdentityAccess, CatalogDelivery, MarketplacePipeline, PartnerHub, TelemetryPipeline) own their models/controllers/services/views/routes/migrations/seeders/tests; root `tests/` keeps only `ExampleTest`; per-module suites run green (`php artisan test Modules/<M>/tests`). The authoritative ownership map is **`MODULE_OWNERSHIP.md`** (repo root).

### 15.1 Why the current structure won't scale

| Current characteristic | Problem at scale |
|---|---|
| Controllers grouped by *role* (Admin/Partner/User) not *domain* | Every new feature touches 3–4 controllers across role groups; merge conflicts and coupling grow |
| All business logic in controllers | No reusable service layer; checkout/payout logic cannot be unit-tested or reused by API |
| One giant `routes/web.php` | 60+ routes in one file; no per-domain isolation |
| Models shared freely across domains | `User` and `Product` are touched by every feature — no ownership boundary |
| UI/backend wiring by convention (inline JS/CSS) | Design system cannot be versioned/tested as a unit |
| No audit/telemetry layer | No observability to diagnose failures in payment/mail flows |

### 15.2 Target: modular monolith — what it is

One application, one deployment, one database — but the codebase is organized into **vertical modules (bounded contexts)**, implemented with the **`nwidart/laravel-modules` ^13 package**, following the exact conventions proven in the **Atlas-Learning** reference project (a 4-module LMS monolith built with this architecture).

**Key rule:** modules must be independently *understandable* and *testable*. This gives you 80% of microservice benefits (team parallelism, clear ownership, testability, future extraction) with none of the distributed-system costs (network, eventual consistency, ops) — which is exactly the right trade for a platform that must scale to "many users and many partners" from a single team/single server today.

### 15.3 Proposed modules (5 modules + Core) — revised after Atlas-Learning review

The original 13-module proposal was **consolidated to 5 modules + core**, mirroring Atlas-Learning's proven boundaries (its whole LMS runs on 4 modules; large modules are normal and healthy). Naming follows the Atlas convention (`PascalCase`, capability-based):

| # | Module | Owns (tables) | Current code that moves here | Atlas analog |
|---|---|---|---|---|
| 1 | **IdentityAccess** | users, personal_access_tokens, password_reset_tokens, sessions, wishlists | User, Wishlist, AuthController, AdminUserController, UserController, Admin/Partner middleware (role guards), WelcomeMember + UserStatusUpdated mail, auth views, admin users views, profile + wishlist/archive views, admin dashboard, API auth endpoints | IdentityAccess (auth, RBAC, admin governance, dashboards) |
| 2 | **CatalogDelivery** | products, categories, product_images, product_variants, reviews | Product, Category, ProductImage, ProductVariant, Review, ProductController, CategoryController, PartnerInventoryController (partner-owned product CRUD — like instructor courses live in CourseDelivery), ReviewController + admin reviews views, product/category/review requests, home/shop/product/artisan-profile views, product-card, media management | CourseDelivery (product/course entity lifecycle, incl. owner-side creation) |
| 3 | **MarketplacePipeline** | carts, cart_items, orders, order_items, payments, payouts | Cart, CartItem, Order, OrderItem, Payment, Payout, CartController, OrderController, PaymentController (PayPal), AdminOrderController, AdminPayoutController, PartnerOrderController, PartnerPayoutController, cart/orders/payouts views, order/payment mailables, **commission engine as a service** | (commerce analog — the money + fulfillment pipeline) |
| 4 | **PartnerHub** | partners, partner_products | Partner, PartnerProduct, PartnerController (admin registry + product mapping), Store/UpdatePartnerRequest, partner portal nav + partner dashboard views, public artisan profile management, (future: KYC, self-service profile) | (vendor analog — no direct Atlas counterpart; kept separate because multi-vendor is the platform's core differentiator) |
| 5 | **TelemetryPipeline** | audit_logs (new), email_logs (new) | Chart/analytics data services (partner 30-day series, admin metrics), low-stock alert service, audit logging, rate limiting, health/status | TelemetryPipeline (event tracking, audits, analytics) |

**Core (stays in root `app/`, NOT a module — same as Atlas):**
- **Service layer convention** → each module has its own `app/Services/*Service.php` (e.g., `CheckoutService`, `PayoutService`, `CatalogQueryService`). Controllers are thin; **blades contain NO SQL and NO inline CSS** (queries in services, styles in per-module `resources/assets/scss` + shared layout components). This is a hard rule of the refactor.
- **CSS layer** → shared layout **Blade components** in core: `x-app-layout`, `x-admin-layout`, `x-partner-layout`, `x-guest-layout` (Atlas: `PlatformLayout/AppLayout/GuestLayout/InstructorLayout` + `components/*-layout.blade.php`). Per-module styling lives in the module's own `resources/assets/` compiled via the module's own Vite config.
- `CurrencyService` + `@money` directive, base `Controller`, `CurrencyMiddleware`, `config/currency.php` + `config/shop.php`, pagination partial, `AppServiceProvider`.

**Module count answer (revised):** **5 modules + Core**. Rationale: Atlas proves 4 modules comfortably cover an entire LMS with 900+ files per module; SmartShop's domains (identity, catalog, marketplace pipeline, partners, telemetry) map 1:1. Fewer, larger modules mean fewer cross-module seams to manage and faster refactor. If the platform later needs extraction (e.g., payouts → Stripe Connect), each module's service layer is the extraction seam.

### 15.4 Target directory structure — nwidart/laravel-modules v13 (Atlas convention)

```
Modules/                                  # ROOT-LEVEL (not app/Modules)
├── IdentityAccess/
│   ├── app/                              # PSR-4: Modules\IdentityAccess\ → app/
│   │   ├── Http/Controllers/            # (AdminController, AuthController, ...)
│   │   ├── Http/Middleware/             # RoleAccessGuard analogs
│   │   ├── Models/                      # User, Wishlist, ...
│   │   ├── Services/                    # service layer: queries/domain logic live here
│   │   ├── Providers/                   # IdentityAccessServiceProvider, RouteServiceProvider,
│   │   │                                #   EventServiceProvider
│   │   ├── Events/ + Listeners/         # cross-module communication (e.g., UserDeleted)
│   │   ├── Mail/                        # module-owned mailables
│   │   └── Policies/                    # module-owned policies
│   ├── config/config.php
│   ├── database/
│   │   ├── migrations/                  # module-owned tables (users, wishlists, ...)
│   │   ├── factories/
│   │   └── seeders/                     # IdentityAccessDatabaseSeeder
│   ├── resources/
│   │   ├── views/                       # module blades — NO SQL, NO inline CSS
│   │   └── assets/
│   │       ├── js/                      # module JS (app.js, components/...)
│   │       └── scss/                    # module styling (app.scss)
│   ├── routes/
│   │   ├── web.php                      # loaded via module_path() with 'web' middleware
│   │   └── api.php                      # loaded with 'api' + prefix
│   ├── tests/Feature/ + tests/Unit/     # per-module tests
│   ├── module.json                      # name, alias, providers[]
│   ├── composer.json                    # merged via wikimedia/composer-merge-plugin
│   ├── vite.config.js                   # outDir: public/build/modules/IdentityAccess
│   └── package.json
├── CatalogDelivery/   (same skeleton)
├── MarketplacePipeline/ (same skeleton)
├── PartnerHub/        (same skeleton)
└── TelemetryPipeline/ (same skeleton)

app/                                      # CORE ONLY (Atlas convention)
├── Http/Controllers/                     # base Controller, generic (contact/sitemap/robots)
├── View/Components/                      # x-app-layout, x-admin-layout, x-partner-layout
├── Providers/AppServiceProvider.php
└── Services/CurrencyService.php          # core services stay here
resources/views/components/               # *-layout.blade.php shared design system (CSS layer)
resources/views/layouts/                  # navigation partials
config/modules.php                        # nwidart package config (namespace 'Modules')
modules_statuses.json                     # module enable/disable registry
vite.config.js                            # root: core assets + vite-module-loader.js aggregation
```

**How it is wired (Atlas-proven):**
1. `composer.json` autoload: `"Modules\\IdentityAccess\\": "Modules/IdentityAccess/app/"` (+ factories/seeders namespaces) — **one entry per module**.
2. `module.json` in each module lists its providers → nwidart auto-discovers them (no changes to `bootstrap/providers.php` beyond `AppServiceProvider`).
3. `modules_statuses.json` gates modules on/off.
4. Module `RouteServiceProvider` (extends Laravel's) maps routes via `module_path($name, '/routes/web.php')` under the `web` middleware group and `routes/api.php` under `api` + `api.` name prefix.
5. Module `vite.config.js` outputs to `public/build/modules/<Module>`; root `vite-module-loader.js` collects enabled modules' asset paths; root `vite.config.js` imports it.
6. Module `composer.json` files merged via `wikimedia/composer-merge-plugin`; per-module `package.json` for frontend deps.

### 15.5 Database impact — **detailed analysis**

**Headline: the database is NOT split.** A modular monolith keeps **one MySQL database** (unlike microservices). This is the single biggest de-risking fact: no distributed transactions, no cross-service queries, no eventual consistency.

What *does* change — schema evolution (expand-contract, zero downtime):

| Change | Type | Why | Risk |
|---|---|---|---|
| `payouts` redesign for per-line-item revenue split | **Data model fix** | Multi-partner leak (add `order_item_id`, `rate`, or an allocations table) | High — financial correctness; needs backfill + tests |
| New `profiles` table (1:1 users, GDPR consent timestamps, KYC fields for partners) | Additive | PARTNER_ROADMAP Section D | Low |
| New `audit_logs` table (telemetry) | Additive | Audit trail requirement | Low |
| New `email_logs` (or reuse `failed_jobs`) | Additive | Async failure observability | Low |
| `payments.provider` column — finally add it | Fix no-op migration | Dead fillable | Low |
| `orders` status enum + optional `shipping_address_id` FK | Additive | Fulfillment workflow (partner shipping) | Low–Med |
| `products.slug`, indexes on `products(category_id, price)`, `order_items(product_id)`, `payouts(partner_id, status)` | Additive | Scale: search/sort/payouts at many-users volume | Low |
| `categories.parent_id` (hierarchical) | Optional additive | Future catalog depth | Low |
| `sessions`/`cache` move to Redis | **Infra change** (not schema) | Horizontal scaling (multi-instance) | Low–Med |

**Table ownership map** (who owns what — the contract of the refactor; migrations live in each module's `database/migrations/`):

| Module | Tables it owns (read/write) | Tables it only reads |
|---|---|---|
| IdentityAccess | users, personal_access_tokens, sessions, password_reset_tokens, wishlists | — |
| CatalogDelivery | products, categories, product_images, product_variants, reviews | users, partners (attribution) |
| MarketplacePipeline | carts, cart_items, orders, order_items, payments, payouts | users, products, partners |
| PartnerHub | partners, partner_products | users, products |
| TelemetryPipeline | audit_logs, email_logs | everything (read-only, via services/events) |
| Core (not a module) | — | config, layouts, CurrencyService |

### 15.6 Refactor scope & impact

| Metric | Count | Notes |
|---|---|---|
| Controllers | 20 | Move into 5 modules (IdentityAccess: 6, CatalogDelivery: 5, MarketplacePipeline: 6, PartnerHub: 2, TelemetryPipeline: 0 — dashboards stay in IdentityAccess/PartnerHub); split `ViewController` (→CatalogDelivery), `UserController` (→IdentityAccess) |
| Models | 20 | Move by ownership map; fix `User::wishlists()` relation; add module interfaces |
| Services (new) | 5+ | `CheckoutService`, `PayoutService`, `CatalogQueryService`, `GovernanceService`, `AnalyticsService` — blades must contain **zero** SQL afterwards |
| FormRequests | 4 | Move to CatalogDelivery / PartnerHub |
| Mailables | 5 | Order/payment mails → MarketplacePipeline; user-status mails → IdentityAccess |
| Middleware | 3 | Admin/Partner role guards → IdentityAccess; Currency stays Core |
| Routes | ~65 web + 4 api | Per-module `routes/web.php` (5 files) + `routes/api.php`; **keep all route names identical** so blade views don't change |
| Blade views | ~75 | Move into `Modules/<M>/resources/views/`; layouts → core components (`x-app-layout`, `x-admin-layout`, `x-partner-layout`); **strip inline CSS → per-module scss; strip SQL → services** |
| JS/CSS | inline → assets | Move inline `<script>`/`<style>` to `Modules/<M>/resources/assets/` + per-module Vite builds |
| Migrations | 25 (+~6 new) | Move ownership to module `database/migrations/` dirs (single DB, ordered globally) |
| Seeders | 6 | Move to module seeders (per-module `*DatabaseSeeder` called by root DatabaseSeeder) |
| Tests | 2 → target 60+ | Per-module `tests/Feature` + `tests/Unit`; payout/checkout concurrency tests are the priority |
| `composer.json` | 1 + 5 | Add `nwidart/laravel-modules` + `wikimedia/composer-merge-plugin`; 5 module autoload entries; 5 module composer.json |
| `modules_statuses.json` | new | Module enable/disable registry |

**Estimate:** every PHP file moves, but **logic is preserved** — this is a mechanical reorganization with surgical fixes (wishlist, payout split, review submission, `products.show`). Order of magnitude: **~160 files touched, 0 features removed, ~6–8 weeks** for one engineer working module-by-module (see migration plan), with the app deployable after every step.

**Biggest risks to manage:**
1. Route-name stability (all blade `route()` calls must keep resolving — keep names, only move definitions)
2. `User` model coupling (it's referenced everywhere — treat as part of Shared kernel contracts; IAM exposes `UserRepository`/contracts)
3. Checkout transaction (`OrderController@store` locks + mails) — keep the transaction atomic; extract a `CheckoutService` without changing semantics
4. Payout recomputation — must be validated against a reference dataset before deploy (financial)

### 15.7 Migration plan (strangler pattern, zero downtime)

```
Phase 0  (1 wk)   Pre-flight: install nwidart/laravel-modules + composer-merge-plugin;
                  fix WishlistController, review submission, payout split; add config/shop.php;
                  snapshot DB; baseline tests green.
Phase 1  (1 wk)   Core + scaffolding: layout components (x-app/x-admin/x-partner-layout),
                  vite-module-loader.js + modules_statuses.json, per-module skeletons
                  (IdentityAccess, CatalogDelivery, MarketplacePipeline, PartnerHub,
                  TelemetryPipeline) with module.json + providers.
Phase 2  (1.5 wks) IdentityAccess module (biggest fan-out: User model, auth, middleware,
                  admin member registry, profile/wishlist views, admin dashboard).
Phase 3  (1.5 wks) CatalogDelivery (largest view count: home/shop/product views,
                  ProductController/CategoryController/PartnerInventoryController,
                  reviews + moderation, media management; extract CatalogQueryService —
                  blades lose all SQL).
Phase 4  (1 wk)   PartnerHub (partner entity, admin registry, product mapping, public
                  artisan profile, partner portal views).
Phase 5  (1.5 wks) MarketplacePipeline (highest risk: CheckoutService + PayoutService
                  extraction with full test coverage, PayPal flow, admin/partner order
                  and payout views).
Phase 6  (1 wk)   TelemetryPipeline (audit_logs, email_logs, analytics services, low-stock
                  alerts, rate limiting) + per-module assets/Vite builds + inline
                  CSS/JS → assets migration.
Phase 7  (1 wk)   Hardening: per-module test coverage, docs, CI, staging deploy, load test,
                  cleanup of dead code inventory (§11).
```
After **every phase**: `php artisan migrate` + full test suite + deploy. Total **~8 weeks**, app never down.

### 15.8 Production-level scaling analysis (many users, many partners)

The modular monolith supports this growth path:

| Concern | Today | After modular refactor (target state) |
|---|---|---|
| App instances | 1 droplet, 1 worker | Stateless app → N app instances behind LB; queue workers scaled independently; **sessions/cache → Redis** (required first) |
| DB | single MySQL | Keep single primary; add **read replicas** for catalog/analytics reads; catalog queries cached in Redis (product/category listing), invalidated per write |
| Media | local storage | S3/Cloudinary + CDN; `getImageUrlAttribute()` is the single seam to swap |
| Catalog at scale | N+1 risk in shop views | Query optimization + Redis cache + pagination (already paginated 12/15) |
| Mail | database queue, 1 worker | Dedicated queue `notifications` + email log table (TelemetryPipeline) |
| Payments | PayPal sandbox | Keep PayPal in MarketplacePipeline isolated so Stripe Connect can be added as a second adapter behind `PaymentGateway` interface |
| Settlements | 10% hardcoded | `PayoutService` config-driven; per-line-item split; batch processing via job for large orders |
| Observability | none | audit_logs, request logging, queue-failure logging, health endpoint (TelemetryPipeline) |
| Team | 1 engineer | Each module = a workstream; no merge conflicts across modules; onboarding doc = this doc + per-module READMEs |
| Future extraction | — | If a module (e.g., MarketplacePipeline payouts) ever needs to be its own service, its service layer is the extraction seam — the modular monolith *permits* microservices later, but does not force it |

**Conclusion of analysis:** the modular monolith is the correct target for this platform — it fixes the structural debt (role-grouped controllers, no service layer, no ownership boundaries) that actually blocks scaling, while avoiding the operational complexity of microservices. DB stays single (schema evolves additively), every step is deployable, and it directly unblocks the partner-completion roadmap.

### 15.9 Execution record

Executed 2026-08-16 → 2026-08-17 on `main` (46 commits, `8714b71`..`da1e36f`), SDD-gated (every phase reviewed; final whole-branch review: **"Migration complete and verified"** — 12 tests/32 assertions, 29 migrations FK-safe, 111 routes, all 39 baseline route names intact, `composer validate` clean, `npm run build` green with all module asset pairs).

- **Post-migration additions:** partner console design (`fa24575`, 2026-08-17) — `.pc-*` component layer, filters, confirm modal, dark-mode-safe status tokens, responsive; suite at 19 tests / 53 assertions.
- **Accepted trade-offs** (documented at final review): email blades keep own CSS (mail clients); module `scss` placeholders mostly empty (real styles consolidated in core `resources/css/app.css` + `partner.css`); `Address` stays in Core (imported by `IdentityAccess`'s `User`); `UserFactory` stays in Core bridged via `User::newFactory()`; PartnerHub has no dedicated tests; 3+-partner payout rounding residue persists by design (verbatim split logic mandated); rate-limiter feature test absent (until 2026-08-17 — see §13).

---

## 16. Recommendations & Next Steps

**Completed since this doc was first generated** (2026-08-16 → 17):

- ✅ Wishlist rewrite — toggle + archive work and are tested (`WishlistTest`)
- ✅ Customer review submission — live and tested (`ReviewSubmissionTest`)
- ✅ Multi-partner payout split — equal split with tests (`PayoutSplitTest`)
- ✅ Partner portal completion: self-service profile edit, production-grade partner console (filters, confirm dialogs, empty states, dark mode, responsive)
- ✅ Modular monolith migration — phases 0–7 executed and reviewed (see §15.9)
- ✅ Rate limiting on auth + checkout; business rules centralized in `config/shop.php`

**Still recommended (priority order):**

1. **Security cleanup**: rotate committed Gmail/PayPal credentials; remove `.env` from git history; `APP_DEBUG=false` in production
2. **Partner trust hardening** (PARTNER_ROADMAP Section D): KYC documents, GDPR/CCPA consent timestamps, admin audit-log UI (tables exist via TelemetryPipeline)
3. **Partner reviews breakdown** — let partners see their review scorecard; **low-stock alert email** (service exists; wire the mailer)
4. **API tests + docs** for the Sanctum surface; **contact form** backend (currently `action="#"`)
5. **Production hardening**: media → S3/Cloudinary via the `image_url` seam, dedicated queue worker for `notifications`, CI workflow

---

*End of document. Regenerate/refresh this file after any significant architectural change.*