# SmartShop (LUWI) — Full-App UI & Flow Audit

Date: 2026-08-20
Scope: IdentityAccess, CatalogDelivery, MarketplacePipeline, PartnerHub, global layout/nav (all blades, routes, controllers, CSS, seeders).
Method: three parallel auditors covering auth/admin/user flows, catalog/checkout/orders flows, and partner/global layout; every `route()` call diffed against `php artisan route:list` (140 routes, all named routes resolve). No files modified.

Legend: HIGH (security or broken core flow) / MEDIUM (real defect, limited blast radius) / LOW (polish/dead code).

---

## 1. IdentityAccess — auth, 2FA, profiles, admin users

### HIGH
- **Seeded accounts can never log in.** `UserSeeder.php:18-23` creates `admin@test.com`, `user1/2@test.com` with no `email_verified_at`; the backfill migration `2026_08_19_150001_backfill_email_verified_at.php:10` runs before the seeder. Login (`AuthController.php:102-106`) always routes unverified users to the email verify-email flow; the codes go to a fake inbox. **RESOLVED by owner decision (2026-08-20): no code change; `mafuletil@gmail.com` promoted to admin via SQL on local + prod (real inbox, OTP works).**
- **Google login bypasses the account-status check.** `GoogleAuthController.php:37-44` logs in suspended/pending users who have a linked Google account (password login checks `status` at `AuthController.php:84-88`). Also, admins/partners without 2FA enrolled skip the challenge entirely via Google (only `twoFactorEnabled()` is checked, line 60), while password login forces it (`AuthController.php:108-116`).

### MEDIUM
- **Admin user edit route 500s.** `routes/web.php:55-60` registers the resource incl. `admin.users.edit`, but `AdminUserController` has no `edit()` method → `GET /admin/users/{id}/edit` = `BadMethodCallException`. No edit blade exists.
- **Status cannot be toggled from admin UI.** `admin/users/index.blade.php:44-53` posts a hidden `status` (current value) with the role select; the only mutation is Approve. Suspended users can never be reactivated; active users can never be suspended.
- **Purge (destroy) 500s on FK constraints.** `AdminUserController.php:103-112` — `User::destroy()`; restrictive FKs on `orders.user_id` / `partner_products.vendor_id` → `QueryException` → 500. No guard against purging/demoting other admins or self-demotion (locks the admin out of the console).
- **"Resend Code" on the challenge is broken for admins/partners without 2FA enrolled.** `TwoFactorController.php:74-76` redirects to login when `! $user->twoFactorEnabled()`, but `challenge()`/`verify()` allow un-enrolled admins/partners. Blade always shows the button; after OTP expiry they can't get a fresh code without logging out.
- **Email/password change + 2FA disable demand a code that is never sent first.** `UserController.php:53-58,127-130`, `TwoFactorController.php:146-159` — first submit always fails "code required"; the code is only sent after a failed attempt. No "Send code" button in `users/settings.blade.php:24-29` / `users/security.blade.php:67-72`. Tests pass only because they call `OtpService::issue()` directly.
- **`apiLogin` skips status/verification/2FA** (`AuthController.php:149-165`); `apiRegister` leaves users unverified with no API verify flow.

### LOW
- "Remember me" checkbox does nothing (`login.blade.php:22-23`; `AuthController.php:118` `Auth::login($user)` without remember flag).
- Google "You can disconnect it anytime" promise unmet (`partials/auth-google.blade.php:13-15` vs `partials/twofa-card.blade.php:57-68` — no disconnect button).
- `UserController.php:154` `route('users.index')` doesn't exist (real name `admin.users.index`); dead code (not routed).
- Session-fixation risk in password reset (`PasswordResetController.php:54-58`, no `session()->regenerate()`); dead `setRememberToken` (no `remember_token` column).
- Dead `session()->forget('2fa:otp:…')` (`TwoFactorController.php:61` — OTPs live in cache, not session).
- Leftover debug log `AdminUserController.php:47`; informal "Suicide is not a solution" flash (`:106`).
- Signup offers `role=admin|partner` (`signup.blade.php:48-52`) gated only by pending status — combined with role-only middleware (below) this is the **H1 role-escalation hole** (see PartnerHub section).
- `auth/challenge.blade.php:30-35` — `<form>` nested inside a `<p>` (invalid HTML).
- Dead `session(['2fa.required' => true])` in GoogleAuthController (`:74-76`); vestigial `two_factor_secret` in User `$fillable`/casts (column dropped).
- No middleware enforces 2FA enrollment — the only pressure is login-time challenge, which Google login can skip (HIGH above).

---

## 2. CatalogDelivery + MarketplacePipeline — catalog, cart, checkout, orders, admin screens

### HIGH
- **Filter drawer on mobile: Apply Filter unreachable.** `Modules/CatalogDelivery/resources/assets/scss/app.scss:162-179` — `.filter-drawer` is `position:fixed; height:100vh; width:400px; padding:3rem` with **no overflow scroll**; at ≤768px only width→100% (`:236`); body scroll is locked while open (`shop.blade.php:12`). Drawer content ≈700-750px tall vs ~667px phone viewport → Apply Filter + Reset land below the fold, untappable. **Fix: `overflow-y:auto`, `height:100dvh` (fallback 100vh), smaller mobile padding, safe-area bottom padding.**
- **Delete flows 500 on FK constraints.** `ProductController.php:106-110`, `CategoryController.php:49-53`, `PartnerInventoryController.php:165-174,40-61` — hard `Model::destroy()` against RESTRICT FKs (`order_items`, `cart_items`, `reviews`, `products.category_id`) → `QueryException` → 500. The categories admin UI warns "may orphan products" but the FK actually blocks deletion.
- **Admin orders list has no pagination render.** `AdminOrderController.php:14` paginates 15; `admin/orders/index.blade.php` never renders `$orders->links()` → only the first 15 orders are ever visible. No empty state either.

### MEDIUM
- **Cart remove is an IDOR.** `CartController.php:85-91` — `remove($id)` has no ownership check; any authenticated user can delete another user's cart item (add is correctly scoped at `:50`).
- **PayPal capture match suspected.** `PaymentController.php:80` saves `transaction_id = $response['id']` (PayPal ORDER id) but `:112` matches on the capture response id — the capture response `id` is the capture id in PayPal Orders v2, so the lookup likely never finds the pending Payment. **Needs a live sandbox test to confirm; flagged, deferred.**
- **Add-to-Bag shown to guests but route is auth-only** (`product.blade.php:44-58`; `cart.add` behind `auth`) → guest click bounces to login, intent lost.
- Checkout: order commits + cart clears inside transaction, then `OrderConfirmed` mail after commit inside same `try` — a mail failure reports "Order failed" although the order exists.
- `PaymentController::capture` has no try/catch around PayPal API / `PaymentSuccess` mail → network/SMTP failure after capture = 500.
- Admin product list N+1 (`ProductController.php:17`, `admin/products/index.blade.php`).

### LOW
- `via.placeholder.com` onerror fallback (`admin/products/index.blade.php:33`) frequently blocked.
- Hardcoded `home.blade.php:9` eyebrow "Collection / 26" (drifts from real count).
- Stale "TODO: not implemented" comments for implemented features (`CartController.php:16-18,36-38`, `OrderController.php:66-70`).
- Hardcoded real-looking contact info (`contact.blade.php:13,17,21` — m.luwi0049@uca.ca.ma, Marrakech) presented as production data.
- PayPal donation link in 3 places incl. order history (`about.blade.php:44`, `app-layout.blade.php:163`, `orders/index.blade.php:15`).
- Boilerplate product description in `ProductSeeder.php:23`.
- Empty `CatalogDeliveryDatabaseSeeder.php` (no-op).

---

## 3. PartnerHub + global layout/nav

### HIGH
- **Self-serve signup + role-only middleware = role escalation.** `AdminMiddleware.php:18-22` / `PartnerMiddleware.php:18-23` allow any user with `role === 'admin'`/`partner` regardless of `status`; public signup lets anyone pick those roles (`signup.blade.php:48-52`) → a self-registered pending "admin" gets full `/admin/*` access immediately; the "Requires Confirmation" label is cosmetic.
- **"Establish New Partner" creates an orphaned partner record.** `PartnerController.php:35-46` — `Partner::create` only; `StorePartnerRequest.php:20-28` validates `user_id` exists but never promotes the user to `role=partner`. The user keeps `role=user`, the Artisan Portal dropdown (`app-layout.blade.php:82-85`) never appears, and `/partner/*` says "Access denied". The inverse path (`AdminUserController.php:59-64,86-91`) does auto-create the Partner record on role change — the two onboarding paths are inconsistent.

### MEDIUM
- Admin pages 500 when related records are deleted (no `?->`/`??`): `admin/orders/index.blade.php:30`, `admin/orders/show.blade.php:70`, `admin/payouts/index.blade.php:28-29`, `admin/dashboard.blade.php:124`.
- Admin orders & payouts tables have no empty state (plain `@foreach`).
- `session('error')` flashes are swallowed by the global toast (`app-layout.blade.php:132-140` renders only `status`/`success`/`$errors`); several flows rely on `back()->with('error', …)` (`PartnerController.php:44,65,85,117`, `AdminOrderController.php:38`).
- Dead `show`/`edit` routes 500 if visited directly (no controller methods): `partner.inventory.show` (`CatalogDelivery/routes/web.php:75`), `admin.categories.show` (`:46-53`), `admin.users.edit` (IdentityAccess).
- Partner profile "Archived pieces" stat is the member's wishlist count, not inventory (`PartnerProfileController.php:29`).

### LOW
- Public partner profile has no empty state (`partner_profile.blade.php:12-19`).
- Admin dashboard "Active Orders" counts only `status='pending'` (`GovernanceService.php:17`).
- Status badge map lacks `active`/`suspended` → gray badges in admin Members table (`status-badge.blade.php:2-11`, `admin/users/index.blade.php:56`).
- "Hello World" scaffold pages (`PartnerHub/views/index.blade.php`, `CatalogDelivery/views/index.blade.php`, `MarketplacePipeline/views/index.blade.php`).
- Empty `PartnerHubController` methods + API resource returning HTML/500s (`routes/api.php:7`).
- Typos/leftovers: "ODO 1:" (`StoreProductRequest.php:13-16`), "YOUR TASK START/END" (`Product.php:87-100`).
- Profile-tabs "Public Profile" tab points at the edit form, not the public profile URL (`profile-tabs.blade.php:13`).
- Nav/responsive nits: hover-only dropdown above 1140px (no tap on large touch), `.pc-table` horizontal scroll on phones, toast `min-width:300px` overflow on 320px screens, mobile menu doesn't auto-close on tap (`app-layout.blade.php:178-191`), `select-all` script not null-guarded (`partner/inventory/index.blade.php:5-10`), Chart.js CDN with no fallback (`partner/dashboard.blade.php:4`), payout "Details" can 404 (`partner/payouts/index.blade.php:43`).

---

## Verified OK
- All header/footer/dropdown/nav links resolve; no `href="#"` (only legit in-page anchors in `collection.blade.php:15`).
- All forms use correct routes + methods + CSRF (filter, cart, checkout, review, contact, cancel, PayPal, partner profile, inventory CRUD, bulk actions).
- Cart, wishlist, orders, reviews, shop, contacts, payouts empty states handled (admin orders/payouts/categories excepted).
- Admin/partner dashboards compute stats live (GovernanceService/AnalyticsService); quick-action panels valid.
- Mobile nav menu, checkout collapse, product gallery thumbnails, wishlist, pagination (shop + admin products/reviews/contacts/payouts + partner screens) all working.