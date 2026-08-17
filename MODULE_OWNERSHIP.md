# Module Ownership Registry
**SmartShop — Modular Monolith Architecture**

This document is the single source of truth for module boundaries, table ownership, and service seams. Every table, model, controller, service, route, and view belongs to exactly one authoritative module; the Core (root `app/`) owns only shared infrastructure.

---

## 1. Module Ownership Map

| Module | Owns (tables) | Reads | View alias | Key services |
| :--- | :--- | :--- | :--- | :--- |
| **`IdentityAccess`** | users, addresses, personal_access_tokens, wishlists | products (wishlist join) | `identityaccess::` | `GovernanceService` |
| **`CatalogDelivery`** | products, categories, product_images, product_variants, reviews | users, partners (attribution) | `catalogdelivery::` | `CatalogQueryService` |
| **`MarketplacePipeline`** | carts, cart_items, orders, order_items, payments, payouts | users, products, partners | `marketplacepipeline::` | `CheckoutService`, `PayoutService` |
| **`PartnerHub`** | partners, partner_products | users, products | `partnerhub::` | — |
| **`TelemetryPipeline`** | audit_logs, email_logs | everything (read-only) | `telemetrypipeline::` | `AnalyticsService`, `LowStockAlertService`, `TelemetryService` |
| **Core (root `app/`)** | password_reset_tokens, sessions, cache, jobs (framework) | — | `resources/views/` (layouts) | `CurrencyService` |

**Reality notes (verified against the repo):**
- `addresses` table migrations live in IdentityAccess (Task 7.2), but the `Address` model stays in Core (`app/Models/Address.php`) and is consumed by `Modules\IdentityAccess\Models\User::addresses()` — an accepted exception.
- `partners` / `partner_products` are the live table names (renamed from `vendors` / `vendor_products` by the PartnerHub rename migration).
- Framework tables (`password_reset_tokens`, `sessions`, `cache`, `jobs`) remain in root `database/migrations/`.
- `UserFactory` stays in Core (`database/factories/UserFactory.php`) and is bridged to the module model via `User::newFactory()`; Category/Product factories live in CatalogDelivery.

---

## 2. Service Map

| Service | Module | Responsibility |
| :--- | :--- | :--- |
| `GovernanceService` | IdentityAccess | Admin member registry queries (users index/filter/status) |
| `CatalogQueryService` | CatalogDelivery | Product/category/review queries — blades contain zero SQL |
| `CheckoutService` | MarketplacePipeline | Transactional checkout (lockForUpdate), order creation, mails |
| `PayoutService` | MarketplacePipeline | Payout computation + processing (10% commission) |
| `AnalyticsService` | TelemetryPipeline | Partner 30-day sales series + admin metrics |
| `LowStockAlertService` | TelemetryPipeline | Low-stock detection + email alert dispatch |
| `TelemetryService` | TelemetryPipeline | audit_logs / email_logs writes |
| `CurrencyService` | Core | `@money` formatting, multi-currency (USD/EUR/GBP/MAD) |

---

## 3. Per-Module Inventory

### 3.1 IdentityAccess
- **Models:** `User`, `Wishlist`
- **Controllers:** `AuthController` (web + Sanctum API), `AdminDashboardController`, `AdminUserController`, `UserController`, `WishlistController`, `IdentityAccessController` (scaffold)
- **Mailables:** `WelcomeMember`, `UserStatusUpdated`
- **Migrations:** users, addresses, personal_access_tokens, wishlists (+ status/confirmation columns)
- **Seeders:** `IdentityAccessDatabaseSeeder`, `UserSeeder`
- **Routes:** `routes/web.php` (auth, profile, wishlist, `admin.*`) + `routes/api.php` (login/register/user)
- **Tests:** `tests/Feature/WishlistTest`

### 3.2 CatalogDelivery
- **Models:** `Product`, `Category`, `ProductImage`, `ProductVariant`, `Review`
- **Controllers:** `ViewController` (home/shop/product/about/contact), `ProductController`, `CategoryController`, `ReviewController` (moderation), `PartnerInventoryController`, `CatalogDeliveryController` (scaffold)
- **Services:** `CatalogQueryService`
- **Factories:** `CategoryFactory`, `ProductFactory`
- **Migrations:** products, categories, product_images, product_variants, reviews (+ review status column)
- **Seeders:** `CatalogDeliveryDatabaseSeeder`, `CategorySeeder`, `ProductSeeder`, `ReviewSeeder`
- **Routes:** `routes/web.php` (storefront + `admin.products|categories|reviews` + `partner.inventory`) + `routes/api.php` (`/api/catalog`)
- **Tests:** `tests/Feature/ReviewSubmissionTest`, `tests/Feature/CategoryIndexSmokeTest`

### 3.3 MarketplacePipeline
- **Models:** `Cart`, `CartItem`, `Order`, `OrderItem`, `Payment`, `Payout`
- **Controllers:** `CartController`, `OrderController`, `PaymentController` (PayPal), `AdminOrderController`, `AdminPayoutController`, `PartnerOrderController`, `PartnerPayoutController`, `MarketplacePipelineController` (scaffold)
- **Services:** `CheckoutService`, `PayoutService`
- **Mailables:** `OrderConfirmed`, `OrderCancelled`, `PaymentSuccess`
- **Migrations:** carts, cart_items, orders, order_items, payments, payouts
- **Seeders:** `MarketplacePipelineDatabaseSeeder`, `OrderSeeder`
- **Routes:** `routes/web.php` (cart, orders, paypal, `admin.orders|payouts`, `partner.orders|payouts`) + `routes/api.php` (scaffold)
- **Tests:** `tests/Feature/CheckoutFlowTest`, `tests/Feature/PayoutSplitTest`

### 3.4 PartnerHub
- **Models:** `Partner`, `PartnerProduct`
- **Controllers:** `PartnerController` (admin registry + product mapping), `PartnerDashboardController`, `PartnerProfileController`, `PartnerHubController` (scaffold)
- **Migrations:** partners (renamed from vendors), partner_products (renamed from vendor_products) (+ details, user_id columns)
- **Seeders:** `PartnerHubDatabaseSeeder`
- **Routes:** `routes/web.php` (`/artisan-profile/{id}`, `admin.partners`, `partner.dashboard|profile`) + `routes/api.php` (scaffold)
- **Tests:** none

### 3.5 TelemetryPipeline
- **Models:** `AuditLog`, `EmailLog`
- **Controllers:** `TelemetryPipelineController` (scaffold)
- **Services:** `AnalyticsService`, `LowStockAlertService`, `TelemetryService`
- **Mailables:** `LowStockAlert`
- **Migrations:** audit_logs, email_logs
- **Seeders:** `TelemetryPipelineDatabaseSeeder`
- **Routes:** `routes/web.php` (`/health`) + `routes/api.php` (scaffold)
- **Tests:** `tests/Feature/TelemetryTest`

---

## 4. Cross-Module Rules

1. **Read via the owning module's services.** Cross-module data access goes through the owning module's service layer (or model for simple reads) — no SQL in blades, no direct table writes across boundaries.
2. **Blades never query.** All queries live in services; controllers stay thin.
3. **Single database.** One MySQL database; schema evolves additively via each module's own migrations (expand-contract, zero downtime).
4. **New features land in their module.** New tables/models/controllers/routes/views go into the owning module (see map above), not Core.
5. **Route names are stable contracts.** Blade `route()` calls resolve across modules; route *definitions* move, names do not.
6. **Tests run per module.** `php artisan test Modules/<Module>/tests`; the root `tests/` suite keeps only `ExampleTest`.
7. **Migration order matters.** Module migrations are timestamped against a global sequence; Task 7.2 preserved creation order.