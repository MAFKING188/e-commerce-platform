# SmartShop — Catalog Images, Collection Catalog, Mobile Filter + Sweep Fixes (2026-08-20)

## Context

Follow-up to the 2026-08-19 dead-ends/catalog task. Owner review + full-app sweep (see
`docs/AUDIT-2026-08-20-full-app-sweep.md`) surfaced four known items plus a set of
security/functional defects. The admin-login problem was resolved without code changes
by promoting the real account `mafuletil@gmail.com` to `role=admin` via SQL on local + prod
(real inbox → the existing email-OTP challenge works); the OTP-bypass idea is dropped.

## Scope

### A. Product images — curated real photos (approved)
- Every one of the 105 products gets an image that plausibly depicts it.
  Source candidate Unsplash photo IDs per product type (cosmetics for Beauty, toys for
  Toys, sports gear for Sports, electronics for Electronics, etc.).
- **Every image URL is curl-verified (HTTP 200 + image content-type) before shipping**;
  any product without a verified match gets a clean category-tinted placeholder
  (CSS gradient + product initial) rather than a wrong photo.
- Single source of truth: `Modules/CatalogDelivery/database/seeders/CatalogInventory.php`
  gains a full name→image map (replacing the index-into-18 array). `ProductSeeder` and a
  new data migration share it so fresh installs and live DB converge.
- Data migration `2026_08_20_180001_curated_product_images`:
  - re-assigns images on all existing rows (old + new) from the curated map,
  - fills the 5 products that currently have no image row (#71, #73-76),
  - drops nothing else; idempotent on fresh installs (guarded on empty tables).
- Products without a curated photo: fallback placeholder via a `placeholder_url` accessor
  using `picsum.photos/seed/{slug}` + CSS tint by category.

### B. Collection page — full browsable catalog (approved)
- `ViewController::collection()` stops reusing `CatalogQueryService::home()`.
  New query method: all products grouped by category, ordered newest-first, eager-loaded.
- `catalogdelivery::collection` view becomes: brand hero + category jump-nav + one grid
  section per category with `id` anchors (`#electronics`, `#clothing`, `#home-kitchen`,
  `#books`, `#beauty-wellness`, `#sports-outdoors`, `#toys-games`). Each section reuses
  `components.product-card`.
- Homepage stays the editorial landing (hero + Editor's Choice + New Arrivals) — no change.
- Nav "Collection" stays `route('collection')`. Footer anchors repointed to the homepage
  sections so the two pages no longer overlap: "New Arrivals" → `route('home')#new-arrivals`,
  "Featured" → `route('home')#editor-choice`.

### C. Mobile filter drawer (root cause confirmed)
`app.scss` `.filter-drawer`:
- `height: 100dvh` with `height: 100vh` fallback,
- add `overflow-y: auto`,
- mobile (≤768px): reduce `padding` to `1.5rem`, shorten `gap`,
- bottom padding `calc(1.5rem + env(safe-area-inset-bottom))` so the Apply/Reset buttons
  are reachable with the on-screen keyboard/browser chrome.
Verify in a phone viewport (agent-browser) that "Apply Filter" is visible and tappable.

### E. Sweep fixes (approved)
1. **Role escalation hole** — `AdminMiddleware` + `PartnerMiddleware` require
   `status === 'active'` in addition to role. Keeps the intended "Requires Confirmation"
   flow but makes it real: pending self-registered admins/partners are denied with a clear
   message until the admin approves them.
2. **Google login** — enforce the same `status` check as password login; force the 2FA
   challenge for `isAdmin() || isPartner()` even when 2FA is not enrolled (align with
   `AuthController::login`); remove the dead `2fa.required` session key.
3. **FK-safe deletes** — wrap product/category/partner-inventory destroy + bulk delete in
   try/catch; on a constraint violation return a friendly flash ("Cannot delete: it is
   referenced by orders/cart/reviews") instead of 500.
4. **Admin orders pagination** — render `$orders->links()` + an empty state.
5. **Cart remove IDOR** — scope the delete to `auth()->id()` and return 404 if not owned.
6. **Challenge resend** — allow resend whenever a `2fa.pending` session exists (not only
   when 2FA is enrolled).
7. **Code-never-sent dead-end** — add "Send code" buttons to email-change, password-change,
   and 2FA-disable forms; the button issues the OTP via the existing services and the form
   then validates against it (mirrors the existing verify-email resend pattern).
8. **Partner onboarding orphan** — `PartnerController::store` sets the selected user to
   `role=partner` + `status=active` (matching the admin-flow behavior in
   `AdminUserController`, which auto-creates the Partner record on role change to partner);
   guard against assigning admin/partner users; friendly error otherwise.

### Deferred (documented in audit; fix on request)
PayPal capture-match verification (live sandbox test), dead `show`/`edit` routes 500 on
direct visits, partner profile stat, hardcoded contact info, placeholder.com fallback,
mobile-menu auto-close, status badges, empty states (admin categories), "Collection / 26"
hardcode, boilerplate seed descriptions, remember-me flag, Google disconnect button,
`apiLogin` hardening, seeded-account verification.

## Data flow
- Images: map lives in `CatalogInventory` (code, not DB) → migration + seeder write
  `product_images.url` from it → `Product::image_url` renders it (unchanged).
- Collection: `ViewController@collection` → `CatalogQueryService::collection()` →
  `[categories => [...each with products], ...]` → view sections.
- Auth: middleware status check in `handle()`; Google controller status check in the
  existing-user branch; challenge resend condition widened; bypass of challenge for
  admins/partners with no 2FA in both login paths (password already does; Google now will).

## Error handling
- FK deletes: catch `QueryException` → flash "Cannot delete X: it is referenced by existing
  orders, carts, or reviews." No 500s.
- Admin user edit: new `edit()` method + blade; update validates role/status/otp flags;
  guards self-demotion and admin-purge; destroy wrapped with the same FK-safe handling.
- Partner promotion: invalid target role → friendly flash.

## Testing
- CatalogCoherenceTest: extend — every product has an image; no product shares an image with
  a product in a different category; placeholder fallback only for products without a
  curated photo (expect zero after curation).
- CollectionPageTest: update — `/collection` renders each category section + anchors; home
  page still renders editorials; footer anchors point at home sections.
- Auth tests: admin/partner middleware rejects pending/suspended users; Google login
  rejects non-active; challenge resend works for un-enrolled admins/partners.
- Security tests: cart remove by non-owner returns 404/403; self-demotion blocked.
- Delete tests: product in order/cart/review delete returns friendly error, not 500.
- Existing suite must stay green.

## Out of scope (YAGNI)
- No soft-deletes, no image upload migration, no real CDN, no category management UI,
  no Google disconnect button, no OTP bypass.