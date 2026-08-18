# Profile Separation & Full-Detail Collection — Design

> **Date:** 2026-08-18
> **Status:** Approved (user: "You can go into implementation")

## Problem

The current profile pages (built in the previous cycle) stack every section on one long page per actor: buyer `/profile` mixes activity timeline + order history + address/password + account details; admin `/admin/profile` mixes a Recent Acquisitions table into the profile; partner `/partner/profile` mixes My Orders. Heavy single pages cause information overload, which worsens on small screens once responsive design lands.

Additionally, not all person attributes are collected/surfaced: `users.status` is never shown, `users.id` is never shown, and there is no phone field on the person table at all.

## Goal

- Split each profile into **separate light pages**, one per section, with a real subnav (links + active states).
- **Collect all person attributes** — every meaningful column of `users` and `addresses` is displayed or editable on the profiles.
- Keep pages light so the upcoming responsive pass is safe; include minimal responsive rules for the profile shell now.
- Constraints unchanged: route names immutable (only ADD new names), zero inline styles (emails excepted), no emojis in UI text (SVG icons, `★` allowed), no new dependencies.

## Attribute Inventory (verified against migrations + live DB)

**users (9 columns):**

| Column | Collect/display |
|---|---|
| `id` | NEW: "Member #000123" on identity card (zero-padded) |
| `name` | Editable (settings) ✓ existing |
| `email` | Editable (settings) ✓ existing |
| `password` | Changeable (security) ✓ existing |
| `role` | Badge on identity header ✓ existing |
| `status` | NEW: status chip (Active / Pending / Suspended) on identity card |
| `avatars` | Upload in header ✓ existing |
| `created_at` | "Member since M Y" ✓ existing |
| `updated_at` | Internal — not collectible |

**addresses (8 columns):** line1, line2, city, state, zip, country, is_primary — all editable on the Address & Security page ✓ existing.

**Missing from schema:** `users.phone` (nullable string) — NEW column, editable in settings, displayed on identity card (self-only).

## Architecture

### Route map (only new names are `profile.security`, `profile.settings`)

| Route | Page | Content |
|---|---|---|
| `GET /profile` (`profile`) | Buyer overview | Identity header (avatar + upload, name, role/tier/verified badges, member-since) + stats strip (orders placed / total spent / archived) + identity card (email, phone, status chip, member #, member since) + activity timeline (8) + quick links |
| `GET /profile/security` (`profile.security`) NEW | Address & Security — **shared by all roles** | Address form (6 fields) + password change (current/new/confirm) |
| `GET /profile/settings` (`profile.settings`) NEW | Settings — **shared by all roles** | Account details form: name, email, phone |
| `GET /admin/profile` (`admin.profile`) | Admin overview | Identity header + platform pulse stats (revenue / active orders / members / pending reviews) + timeline (8) + link to Command Center. **Recent Acquisitions table REMOVED.** |
| `GET /partner/profile` (`partner.profile.show`) | Partner overview | Identity header + atelier business card (**name, description, website link, contact info** — surfaced for the first time) + stats (pieces in catalog / pending earnings / archived) + timeline (8) + links |

### What is removed (separation)

- Buyer: order history list on profile → Orders tab links to existing `orders.index`
- Admin: Recent Acquisitions table deleted from profile → links to `admin.dashboard` (data lives there)
- Partner: My Orders list → My Orders tab links to existing `partner.orders.index`

### Subnav tabs (real links, active state)

- Buyer: Overview · Orders (`orders.index`) · Address & Security (`profile.security`) · Settings (`profile.settings`)
- Admin: Overview · Command Center (`admin.dashboard`) · Address & Security (`profile.security`) · Settings (`profile.settings`)
- Partner: Overview · My Orders (`partner.orders.index`) · Address & Security (`profile.security`) · Public Profile (`partner.profile.edit`)

### Profile shell component changes

`resources/views/components/profile-layout.blade.php`:
- `$subnav` items become `['href' => ..., 'label' => ..., 'active' => bool]`; component renders `<a href="{{ $item['href'] }}" class="profile-subnav__link {{ $item['active'] ? 'is-active' : '' }}">`
- `is-active` style: brand accent underline + text-900
- New optional `$identity` details block rendered from `$user` (email, phone, status chip, member #) — used by all three overviews
- Responsive rules appended to the shell CSS block (`@media (max-width: 768px)`): header stacks (avatar centered, meta wraps), subnav horizontal scroll (`overflow-x: auto`), card padding 1.25rem, stats already auto-fit

### Status chip

`User::statusChip(): array` → `['label' => 'Active'|'Pending'|'Suspended', 'tone' => 'ok'|'warn'|'danger']`; rendered as `.profile-badge--status-ok|warn|danger` (reuses `--pc-ok-bg/--pc-ok-fg` style tokens with fallbacks).

### Member number

`User::memberNumber(): string` → `'Member #' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT)`.

## Data changes

1. Migration `2026_08_18_000003_add_phone_to_users_table.php`: `string('phone')->nullable()->after('email')`.
2. `User::$fillable` += `phone`.
3. `UserController::updateProfile()`: add `'phone' => 'nullable|string|max:30'` validation; `$user->update($request->only(['name', 'email', 'phone']))`.
4. `UserController::show()`: pass `$identity` (email, phone, status chip, member number, member since) + `$timeline` + `$stats` — no order list.

## Error handling

- Address/password/account forms redirect back with session errors → existing toast + error list behavior on the pages (keep the `profile-flash`-style error display? no — use existing toast + inline `@error` where present; forms already rely on session `success` toasts via app-layout).
- Password change: current password wrong → validation error on `current_password` (existing behavior).
- Avatar upload: validation errors redirect back (existing).

## Testing

- `ProfileTest`: update overview assertions (`Orders / Activity` label → `Orders` tab link; keep `Address & Security`), add:
  - security page renders address + password forms (`/profile/security` 200)
  - settings page renders and saves phone (`PUT /profile/update` with phone persists)
  - settings/security pages render for partner and admin roles too
- `AdminProfileTest`: `assertDontSee('Recent Acquisitions')` on `/admin/profile`; tab links present
- `PartnerProfileShowTest`: assert business card shows website + contact info
- New: guest access to `/profile/security` and `/profile/settings` → redirect (auth middleware)
- `IdentitySignalsTest`, `LegalPagesTest`, checkout suites: unchanged

## Out of scope

- Full-site responsive pass (separate future work) — only profile shell responsive rules here
- Partner business card editing (already exists at `partner.profile.edit`)
- Admin editing of other users' profiles (exists at admin users management)