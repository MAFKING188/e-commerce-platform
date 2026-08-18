# Production Uniformity: Console Design System, Delivery Data, Full Profiles

Date: 2026-08-18
Status: Implemented & verified

## Context

Post-QA audit of the SmartShop platform surfaced three production-level gaps:

1. **No visual uniformity.** The admin console used ad-hoc inline styles (`style="..."`),
   emoji glyphs, and storefront classes, while the partner console used a dedicated
   `.pc-*` design system. Pages rendered with default browser chrome (Laravel default
   titles), and some pages did not even include the console navigation.
2. **Orders carried no delivery data.** The `orders` table had zero delivery columns;
   checkout collected nothing, admins could not ship to a real address, and buyers had
   no delivery record in order history.
3. **Profiles were partial.** The `addresses` table has 8 fields (`line1, line2, city,
   state, zip, country, is_primary`) but the buyer profile exposed only 4.

## Approved Decisions

- **Consoles + storefront polish:** the `.pc-*` layer becomes the shared console design
  language for BOTH admin and partner consoles; the storefront keeps its premium
  identity but loses inline styles and emojis.
- **Per-order delivery block:** checkout collects recipient name/phone + shipping
  address; the buyer's primary address pre-fills the form; delivery is displayed per
  order in buyer history (full), admin order details (full), and partner order details
  (city/country only — privacy).
- **Inline SVG icons** (24×24 stroke style, matching the partner console) replace all
  emoji glyphs. `★` rating stars remain (standard rating affordance).
- **Nav IA (Approach A):**
  - Admin: Overview | Orders | Products | Partners | Payouts | Reviews | Categories | Members
  - Partner: Dashboard | Inventory | Orders | Earnings | Profile (reordered)

## What Changed

### Data layer
- Migration `2026_08_18_000000_add_delivery_fields_to_orders_table.php` (applied):
  `recipient_name, recipient_phone, shipping_line1, shipping_line2, shipping_city,
  shipping_state, shipping_zip, shipping_country, delivery_notes` (all nullable).
- `Order` model: fillable list + `shipping_address` accessor
  (`"line1, city, country"`).
- `CheckoutService::checkout(User $user, array $delivery = [])` — persists delivery via
  a whitelist (`array_intersect_key`).
- `OrderController::store` — validates delivery (recipient_name, recipient_phone,
  shipping_line1, shipping_city, shipping_country required; rest optional).
- `CartController::index` — passes the buyer's primary address to the cart view for
  pre-fill.
- `UserController::updateProfile` — saves all 8 address fields.
- `App\Models\Address::$fillable` — **bugfix**: was silently stripping `line2, state,
  zip` on mass assignment (the DB columns existed; the model forbade them).

### Console design system (admin + partner)
- `partials/admin-nav.blade.php` rebuilt: `.pc-nav` segmented tab bar with inline SVG
  icons and concrete labels (Overview|Orders|Products|Partners|Payouts|Reviews|
  Categories|Members); loads `resources/css/partner.css` via `@section('styles')`.
- `partials/partner-nav.blade.php`: reordered to Dashboard | Inventory | Orders |
  Earnings | Profile.
- All 17 admin blades refactored from inline styles to the `.pc-*` layer:
  dashboard, orders index/show, products index/create/edit, users index, partners
  index/create/edit/show, payouts index, reviews index, categories index/create/edit.
  Missing `@include('partials.admin-nav')` added to products/categories/partners forms
  and admin order show.
- New `.pc-*` primitives added to `partner.css`: `pc-btn-sm` (standalone + `--ok`/
  `--danger` variants), `pc-row-actions`, `pc-pagination`, `pc-role-select`,
  `pc-form-actions`, `pc-field--full`, `pc-header__date`, `pc-card__title`,
  `pc-stat.is-alert`, `pc-wrap-narrow`, `pc-filter__input--sm`, `is-strong` table
  modifier, `--pc-danger-border` token (light/dark).
- Shared `status-badge` partial reused across admin (orders/payouts/users).

### Storefront
- Cart: checkout form no longer wraps the item list (fixes nested-form invalid HTML);
  "Confirm & Checkout" button uses `form="checkout-form"`; new DELIVERY section
  (8 fields + notes) pre-filled from primary address; `btn-checkout`,
  `checkout-secure-note`, `delivery-*` classes.
- Order history: full rewrite — no emojis, no inline styles; support banner, status
  pills, per-order delivery block, PayPal button with SVG, cancel form with
  `data-confirm`.
- Member profile: all 8 address fields (line2/state/zip added), primary-address
  pre-fill, no inline styles.
- Wishlist + public partner profile: inline styles replaced with classes.
- `app-layout`: theme toggle uses inline SVG (sun/moon swap), `data-confirm` global
  submit interceptor added (replaces scattered `onsubmit="return confirm(...)"`),
  toast glyphs unchanged (text, not emoji).

### Cleanup
- Emoji removed from: about (PayPal CTA), wishlist empty state, admin reviews empty
  state, admin products upload zone, partner inventory flash/remove buttons, partner
  profile flash, admin/partner product media remove buttons.
- Deleted dead views `identityaccess::users/index.blade.php` +
  `identityaccess::users/edit.blade.php` (no routes bound) and dead controller methods
  `UserController::index` / `UserController::edit`.
- `UserController::show` now prefers the primary address (`firstWhere('is_primary')`).

## Verification

- `php artisan test`: 28 passed / 93 assertions (new: delivery persistence +
  validation in CheckoutFlowTest; ProfileTest full-address save + no-clobber).
- `npm run build`: clean (pre-existing `@source` warning only).
- HTTP/browser checks:
  - partner nav renders in new order; `.pc-nav` computed background applied
    (partner.css actually loads — validated in a real browser).
  - all 8 admin pages load partner.css + proper titles; payouts page returns 200
    (null-safe fix holds, `TXN-9F3K2A` renders).
  - admin dashboard: 3 pc-stats (+ `is-alert` low-stock), 5 action panels, recent
    orders table.
  - full E2E checkout via HTTP: delivery persisted (`Test Buyer | +33... | 12 Rue de
    Test | Paris | France | 12 Rue de Test, Paris, France | Leave at door`) and
    displayed on buyer order history; admin order show renders Recipient/Address/
    Notes blocks; partner order detail renders city/country (privacy-shielded).
  - zero `style="..."` attributes remain outside email templates (mail clients strip
    `<style>` blocks, so inline email styles are intentional).

## Follow-ups (out of scope here)

- Storefront pages still carry a few bespoke classes (`editor-stage`, `review-card`,
  `category-card`) — kept for now since they share the same tokens; consolidation into
  `.pc-*` or storefront equivalents is optional polish.
- `partners` table has no slug/commission columns; public partner profile routes are
  not yet linked in navigation.
