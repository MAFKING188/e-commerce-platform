# Real Profile Pages for All Actors + Legal Pages

Date: 2026-08-18
Status: Approved — ready for implementation

## Context

The user's core complaint: current profile pages are "too basic" — they read as
settings forms, not profiles. Real applications have profile pages that visibly
belong to a real person. The platform also has no legal pages despite footer links
(Privacy, Shipping, Returns — all dead `href="#"`, no Terms at all).

Hard requirement from the user: **"We do not want bots on the site."** Concretely
(approved scope): profiles must display human identity signals that bots cannot
fabricate — verified badge, member tier, organic activity timeline. No new auth
friction (no captcha/email-verification in this work).

## Approved Decisions

- **All three actors** get view-first profile pages built on a **shared profile
  shell** (one Blade component, one design language): buyer, partner, admin.
- **Human identity signals** derived from existing data (no new schema): member
  tier, verified badge, activity timeline, reviews written.
- **Avatar upload** (users table gets an `avatars` column; letter-initial fallback
  everywhere).
- **In-profile password change** for all actors (current + new + confirm; dead
  "Forgot password?" login link removed).
- **Four legal pages** (Privacy, Terms, Shipping, Returns) with production-grade
  static copy; footer links all four.

## Architecture & Routing

**Shared profile shell** — `resources/views/components/profile-layout.blade.php`
(anonymous component):
- Identity header card: avatar (image or letter fallback), name, role badge,
  member-since, verified badge + tier badge, avatar upload button.
- Sticky section tabs rail: Overview | Orders / Activity | Address & Security |
  Settings.
- Content `$slot`. Console profiles (partner/admin) keep `.pc-nav` above the shell;
  buyer keeps storefront chrome. Shell uses shared design tokens (`app.css` +
  `partner.css` layer) so it renders natively in both contexts.

**Routes** (existing route names untouched; new names only):
| Actor | View | Edit |
|---|---|---|
| Buyer | `/profile` (existing `profile`) — redesigned in place | inline sections on same page |
| Partner | `/partner/profile` → NEW `partner.profile.show` | `/partner/profile/edit` (`partner.profile.edit`) kept as-is |
| Admin | `/admin/profile` → NEW `admin.profile` | inline sections on same page |

**Controllers:**
- `UserController::show` (buyer): add profile stats (orders count, total spent,
  archive count, tier, verified), recent orders, activity timeline, reviews written.
- `AdminProfileController` (new): admin stats (revenue, active orders, members,
  pending reviews) + recent acquisitions + member registry quick links.
- `PartnerProfileController::show` (new): partner record + product count + pending
  payouts + recent orders.
- `UserController::updatePassword` (new): validate current password, new password
  `min:8|confirmed`, rehash, logout-other-devices (session regeneration).
- `UserController::updateAvatar` (new): validate jpeg/png/webp ≤ 2MB, store to
  `public` disk at `avatars/{user_id}.{ext}`, delete previous file.
- `LegalController` (new, CatalogDelivery): `show(string $page)` with whitelist
  `['privacy','terms','shipping','returns']`.

## Profile Shell Sections (actor-specific content)

- **Identity header**: avatar, name, role badge, member-since, verified badge, tier
  badge, avatar upload control.
- **Overview**: stat cards + mini activity feed.
  - Buyer: Orders placed, Total spent, Archived pieces; recent orders list.
  - Partner: Products in catalog, Orders received, Pending earnings; public profile
    preview link (`/artisan-profile/{id}`).
  - Admin: Revenue, Active orders, Members, Pending reviews; console quick links.
- **Orders / Activity**: buyer = full order history (incl. delivery block); partner =
  recent orders with status; admin = recent acquisitions table.
- **Address & Security**: address form (all 8 fields) + password change form.
- **Settings**: buyer/admin = name + email; partner = existing edit form embedded
  (single source of truth).

## Human Identity Signals (anti-bot)

Derived from existing data — nothing a script can fabricate, no new auth friction:

- **Member tier** — `User::memberTier()` accessor, computed from total spent:
  Member ($0), Collector ($500+), Patron ($2,500+), Benefactor ($10,000+). Badge in
  identity header.
- **Verified badge** — `User::isVerifiedMember()`: status active + avatar set +
  primary address + ≥1 completed order. Badge in identity header.
- **Activity timeline** — merged, date-sorted feed of organic events: orders placed
  (with total), reviews written (with rating + product), pieces archived
  (`wishlists.created_at`). Empty for scripted accounts. Shown in Overview.
- **Reviews written** — visible on buyer profile (product, rating, comment excerpt,
  product link).

## Avatar Upload

- Migration: nullable `avatars` string on `users`.
- `POST /profile/avatar` (auth middleware, all actors share the users table):
  `UserController::updateAvatar`, image validation (jpeg/png/webp, max 2MB), store
  `avatars/{user_id}.{ext}` on `public` disk, delete old file on replace. Requires
  `php artisan storage:link` on deploy.
- `User::avatarUrl()` accessor: `asset('storage/...')` or `null`.
- Rendering: profile headers (buyer/partner/admin) + review avatars (storefront
  product reviews + admin moderation) use image with letter fallback.

## Password Change

- `PUT /profile/password` (auth): current password check, new `min:8|confirmed`,
  session regeneration (prevents session fixation), flash success.
- Login page: remove dead "Forgot password?" link.

## Legal Pages

- Routes: `/privacy`, `/terms`, `/shipping`, `/returns` → `LegalController@show`.
- Views: `Modules/CatalogDelivery/resources/views/legal/{privacy,terms,shipping,
  returns}.blade.php` — storefront design language (hero + prose sections),
  production-grade copy (dev template, not legal advice).
- Footer (app-layout): link Privacy, Terms, Shipping, Returns; remove dead `#`
  links.

## Testing & Verification

- New tests:
  - Avatar: upload succeeds + file stored + URL accessor; invalid type rejected;
    replacement deletes old file.
  - Password: wrong current rejected; success persists new hash + regenerates
    session.
  - Tier/verified accessors: boundaries ($499.99 vs $500; unverified vs verified
    conditions).
  - Legal: each route 200 + contains key headings.
  - Activity timeline composition (orders + reviews + wishlist merged, sorted).
- Existing 28 tests stay green; `npm run build` clean.
- HTTP/browser verification: all 3 profile pages render shell + sections; avatar
  upload E2E; password change E2E; legal pages 200 with links in footer.

## Out of Scope

- Email-verification flow, captcha/honeypot at registration (user approved signals
  only; registration protections can be a follow-up project).
- Changing existing route names (immutability constraint).
- Public profile pages for buyers (only partner has a public profile).
