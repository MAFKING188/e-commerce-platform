# Partner Console Design — Production-Level UI

**Date:** 2026-08-17
**Status:** Approved
**Scope:** Partner-facing pages only (7 pages + navigation). No storefront, no admin pages.

## 1. Goal

Upgrade the partner-facing pages of LUWI from functional, inline-styled views to a
purpose-built "partner console": a cohesive, production-level UI that is simple and
straightforward for non-technical users. The console must not look AI-generated or
template-like; it extends the existing LUWI design system with a consistent component
language, complete interaction states (empty, loading, error, confirm), dark-mode-safe
semantics, and responsive behavior. No new dependencies, no schema changes, no route
changes.

## 2. Scope

Pages in scope (spans three modules):

| Page | Module |
|---|---|
| Dashboard | PartnerHub (`partner/dashboard.blade.php`) |
| Orders index | MarketplacePipeline (`partner/orders/index.blade.php`) |
| Order detail | MarketplacePipeline (`partner/orders/show.blade.php`) |
| Earnings / payouts | MarketplacePipeline (`partner/payouts/index.blade.php`) |
| Inventory index | CatalogDelivery (`partner/inventory/index.blade.php`) |
| Inventory create / edit | CatalogDelivery (`partner/inventory/{create,edit}.blade.php`) |
| Profile edit | PartnerHub (`partner/profile/edit.blade.php`) |
| Navigation partial | Core (`partials/partner-nav.blade.php`) |

Out of scope: storefront pages, admin pages, catalog/identity modules' public pages,
route structure, data model, order/payout business logic.

## 3. Visual language

Base tokens are the existing system (slate/blue, Instrument Sans, current radii,
shadows, light/dark themes). The console defines a consistent component language on top.

### 3.1 Status palette via CSS variables

New semantic status variables (light + dark values) replacing hardcoded colors:

- `--status-ok` (in stock / completed / processed)
- `--status-pending` (pending / processing)
- `--status-danger` (low stock / cancelled / refunded)

Each variant provides `-bg` and `-fg` pairs so every badge renders correctly in both
themes. This fixes current hardcoded `#fee2e2` / `#991b1b` badges that break in dark mode.

### 3.2 Page header pattern

Every page: eyebrow badge (e.g. "Order Fulfillment"), large bold title, optional action
button top-right. One `.pc-header` class; unifies `.partner-header` / `.inventory-header`
and inline variants.

### 3.3 Stat cards

Label (uppercase, muted), large value, optional footnote link. Dashboard: 3-up grid
(active inventory, pending payout, total revenue). Earnings: 2-up (total processed,
pending balance). Shared `partials/partner/stat-card.blade.php`.

### 3.4 Tables

One `.pc-table` used across dashboard recent orders, orders index, payouts, inventory:

- Clear header row (surface tint), row hover, right-aligned numeric columns
- Product cell: thumbnail + name + category sub-line
- `min-width` + horizontal scroll wrapper for small screens

### 3.5 Status badges

Pill with colored dot + tinted background, driven by the semantic variables. Shared
`partials/partner/status-badge.blade.php` (status name → variant mapping kept in the
partial).

### 3.6 Empty states

Inline SVG icon, short title, one-line explanation, optional action button. Shared
`partials/partner/empty-state.blade.php`. Used on orders, payouts, inventory, recent
orders.

### 3.7 Forms

Existing `.form-container` / `.form-group` / `.form-control` stay; refinements:

- Focus ring on inputs
- Field-level error messages (currently only a generic banner)
- Helper text where useful
- Two-column grid on inventory create/edit (existing fields, no schema change)

### 3.8 Confirm dialog

One dependency-free modal: overlay + card + title + text + Cancel/Confirm. Driven by
`data-confirm` / `data-confirm-title` / `data-confirm-message` attributes on buttons;
on confirm submits the closest form (bulk delete, delete actions). Esc / backdrop click
cancels. Shared `partials/partner/confirm-modal.blade.php` + small vanilla JS.

### 3.9 Navigation

`partials/partner-nav.blade.php` restyled as a segmented tab bar with inline SVG icons,
clear active state, horizontally scrollable on mobile, inline styles removed.

### 3.10 Chart

Chart.js stays (CDN + existing `dashboard.js`); restyled to the design system: accent
line with gradient fill, muted gridlines, site font family.

### 3.11 Micro-interactions & accessibility

- Button hover/active, table row hover
- `:focus-visible` rings throughout
- `prefers-reduced-motion` respected for modal/chart transitions
- Modal: `role="dialog"`, `aria-modal`, Esc close

## 4. Usability additions

All server-side, additive, backward-compatible:

### 4.1 Filters

- Orders index: search by order ID, status dropdown (whitelist of existing statuses),
  Apply/Reset
- Inventory index: search by name, stock status (all / in stock / low or out of stock)

Implementation: optional `search` / `status` query params in the existing controllers,
whitelist-validated (invalid values fall back to unfiltered), applied via `when()`,
pagination preserved with `->appends(request()->query())`. No new routes.

### 4.2 Confirm dialogs

Bulk delete (inventory) and other destructive actions get the confirm modal. Native
`confirm()` is not used.

## 4b. Per-page composition

- **Dashboard:** 3 stat cards with footnote links ("Manage Portfolio →", "Earnings
  History →"), sales chart card (30-day), recent orders card (compact table: ID, date,
  status badge, Details link) + "View all orders" link. Empty recent orders: empty state.
- **Orders index:** filter bar (search by order ID, status dropdown, Apply/Reset),
  table (ID, date, status badge, View Details button), empty state for no matches,
  pagination preserved.
- **Order detail:** header with order ID + status badge, back link, customer card,
  shipping address card, items table (product, qty, price, line total), totals block.
  (Rendered from existing data; no new queries.)
- **Earnings:** 2 stat cards, payout history table (order ref link, date, net amount,
  status badge, monospace transaction ref), empty state ("No earnings recorded yet…").
- **Inventory index:** header + "Add New Product" button, filter bar (name search,
  stock status), table with checkboxes + bulk delete (confirm modal), per-row Edit,
  empty state with "Add your first product" action.
- **Inventory create/edit:** two-column form grid (name, price, category, stock, image
  URL, description), field-level errors, low-stock hint, save/cancel buttons.
- **Profile edit:** form refinement (business name, bio, website, contact info),
  styled success flash banner, inline field errors.

## 5. Engineering

### 5.1 CSS home

New `resources/css/partner.css`, imported at the top of core `app.css` (single Vite
entry preserved). Add `@source '../../Modules';` to the Tailwind sources so module
blades' utilities are detected. All colors via variables (dark-mode safe).

### 5.2 JS

New `resources/js/partner.js`, vanilla (~25 lines): confirm modal wiring + filter form
helpers. Loaded only on partner pages via the existing `@section('scripts')` +
`@vite(...)` pattern (same as `dashboard.js` today); auto-included in the Vite build by
the laravel-vite-plugin. Storefront does not load it.

### 5.3 Shared partials

`resources/views/partials/partner/`: `stat-card`, `status-badge`, `empty-state`,
`confirm-modal` — following the existing `partials/partner-nav.blade.php` precedent
(core owns shared partner UI).

## 6. Error handling

- Invalid filter values: fall back to unfiltered (never 422)
- Empty filter results: empty state with "clear filters" hint
- Form validation: field-level errors + retained banner for session flash
- Flash messages: styled banner consistent with the console

## 7. Verification

1. Existing test suite stays green (12 tests / 32 assertions at baseline)
2. Small tests added where controller filter behavior changed (status whitelist,
   search param passthrough, pagination appends)
3. `npm run build` green; manifest contains the new `partner.js` entry
4. Browser screenshots of all 7 pages in light + dark themes
5. 375px mobile check on nav, tables, forms

## 8. Guardrails (non-negotiables)

- No new dependencies (no Alpine, no Vue, no UI kit)
- No schema changes, no route changes, no new tables
- No storefront changes, no admin-page changes
- No inline `style=` attributes left in the 7 partner pages (moved to classes)
- No SQL in blades (existing rule), no hardcoded hex colors in partner blades
- Partner pages remain usable without JS (filters are GET forms; confirm modal is
  progressive enhancement — a submit without the modal still works)