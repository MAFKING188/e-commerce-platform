# Email Center — Design Spec

**Date:** 2026-08-20
**Status:** Approved (Q&A roundtrip)

## Background

SmartShop currently sends only transactional mail through nine hard-coded
mailables (`OtpMail`, `PasswordChangedMail`, `PasswordResetMail`,
`UserStatusUpdated`, `WelcomeMember`, `ContactMessageMail`, `OrderCancelled`,
`OrderConfirmed`, `PaymentSuccess`). There is no way for admins or partners to
reach users with notices, newsletters, or notifications, and no reusable,
editable email templates. This spec adds a full **Email Center** as a new
module so that:

- admins can send templated or free-form emails to any user or user group;
- partners can email **their own buyers only**;
- every send is queued, logged, and auditable.

This feature runs in parallel with the approved sweep plan
(`docs/superpowers/plans/2026-08-20-catalog-images-collection-filter-sweep.md`)
whose middleware status gates (E1) this module's routes also use.

## Scope

### In scope

- New module `Modules/EmailCenter` (module convention matching existing modules:
  `module.json`, `Providers/`, `routes/web.php`, `app/Http/Controllers/`,
  `app/Mail/`, `app/Models/`, `database/migrations/`, `database/seeders/`,
  `resources/views/`, `tests/Feature/`).
- Tables `email_templates` and `email_logs` (below).
- Queued markdown mailable `PlatformMail` rendered with the existing branded
  layout (same `app-layout` mail layout used by `ContactMessageMail`).
- Admin pages: Templates (list/create/edit/delete), Compose & send, Send
  history (all logs).
- Partner pages: Compose & send (own buyers only), Send history (own sends
  only). Templates are read-only for partners.
- Placeholder replacement: `{name}`, `{email}` (per recipient).
- Seed 3 starter templates (Welcome/newsletter-style, Notice, Order update).
- Recipient cap 100 per send; `throttle:5,1` on send endpoints.
- Tests (below).

### Out of scope

- Unsubscribe links / newsletter opt-out handling (users have a
  `newsletter_optin` flag; the "newsletter subscribers" group respects it, but
  no per-email unsubscribe link).
- HTML/rich-text bodies (markdown only).
- Scheduled/delayed sends, drip campaigns, attachments.
- Partner-owned private templates.
- Bounce/webhook tracking beyond the `failed` status column.

## Decisions (Q&A roundtrip)

| Question | Answer |
| --- | --- |
| Who can send to whom? | Admin = any user / role groups / opt-in group / individual pick. Partner = own buyers only (users who ordered a product the partner sells). |
| Templates? | Stored, editable templates managed by admins, selected + tweaked per send, plus blank composer. |
| Body format? | Markdown body with `{name}` and `{email}` placeholders, rendered with the branded mail layout. |
| Approach | Dedicated `EmailCenter` module with `email_templates` + `email_logs`, queued per-recipient mail, capped batches (Approach A). |

## Design

### Data model

`email_templates`

| column | type | notes |
| --- | --- | --- |
| id | bigint PK | |
| name | string(100) | required, unique |
| subject | string(150) | required; `{name}` allowed |
| body_markdown | text | required; `{name}`/`{email}` allowed |
| created_by | FK users id, nullable | admin who created it; null = seeded |
| timestamps | | |

`email_logs`

| column | type | notes |
| --- | --- | --- |
| id | bigint PK | |
| batch_id | uuid | one per compose action; may cover many recipients |
| sender_user_id | FK users id | |
| sender_role | string(20) | `admin` or `partner` snapshot |
| recipient_email | string(255) | |
| template_id | FK email_templates id, nullable | null = blank composer |
| subject | string(150) | snapshot with placeholders replaced |
| body_markdown | text | snapshot with placeholders replaced |
| status | string(10) | `pending` → `sent` or `failed` |
| error | text, nullable | failure reason |
| timestamps | | |

Indexes: `email_logs(batch_id)`, `email_logs(sender_user_id)`,
`email_logs(status)`.

### Send flow (compose → send)

1. Validate: subject ≤ 150 chars required; body ≤ 10,000 chars required;
   ≥ 1 recipient; recipients ≤ 100.
2. Build recipient list:
   - **Admin** (groups): `all` (active users, email-verified), `admins`,
     `partners`, `members` (role `user`), `newsletter` (active +
     `newsletter_optin = true`); or individual multi-select (search users by
     name/email, active + verified only).
   - **Partner**: distinct users from
     `Order::whereHas('items.product.partners', fn($q) => $q->where('partners.id', $partner->id))`
     (the exact `Partner::orders()` pattern), active + verified only. Never
     empty → the page shows a hint instead of the send button when the partner
     has no buyers.
3. Per recipient: resolve `{name}` → `user->name`, `{email}` →
   `user->email`; record `email_logs` row (`pending`); dispatch
   `PlatformMail` (markdown, `->queue()`).
4. Toast: "Queued N email(s)". Failures are recorded per-log-row; a failed
   queue send flips the row to `failed` with the error snapshot (the mailable
   itself cannot update the log — the row is marked `failed` via a
   `Queue::failing` listener scoped to `PlatformMail`, or the listener is
   skipped: **decision**: mark `sent` optimistically on dispatch is wrong; use
   the `Queue::failing(PlatformMail::class)` listener in the module provider to
   flip status — simplest reliable hook).
5. Logs page lists newest first with filter by status and a search box
   (email/subject), paginated 15.

### Routes (all new, names immutable from here on)

Admin (added inside the `auth+admin` group in `Modules/IdentityAccess/routes/web.php`
or the module's own `auth+admin`-guarded group — **use the module's own
group** to keep IdentityAccess untouched):

- `GET /admin/email-templates` → `EmailTemplateController@index` (`admin.email-templates.index`)
- `GET /admin/email-templates/create` → `@create` (`admin.email-templates.create`)
- `POST /admin/email-templates` → `@store` (`admin.email-templates.store`)
- `GET /admin/email-templates/{id}/edit` → `@edit` (`admin.email-templates.edit`)
- `PUT /admin/email-templates/{id}` → `@update` (`admin.email-templates.update`)
- `DELETE /admin/email-templates/{id}` → `@destroy` (`admin.email-templates.destroy`)
- `GET /admin/email-compose` → `EmailSendController@compose` (`admin.email.compose`)
- `POST /admin/email-send` → `@send` (`admin.email.send`, `throttle:5,1`)
- `GET /admin/email-logs` → `EmailLogController@index` (`admin.email.logs`)

Partner (module's own `auth+partner` group):

- `GET /partner/email-compose` → `PartnerEmailSendController@compose` (`partner.email.compose`)
- `POST /partner/email-send` → `@send` (`partner.email.send`, `throttle:5,1`)
- `GET /partner/email-logs` → `PartnerEmailLogController@index` (`partner.email.logs`)

Templates are read-only for partners (shown via the compose page select).

### Controllers

- `EmailTemplateController` — standard resource minus `show`; delete is
  FK-safe: `email_logs.template_id` set `null` on delete (FK `nullOnDelete()`)
  so history survives.
- `EmailSendController` — compose (groups + user search + template select +
  preview) and `send` (validate, resolve recipients, dispatch, log, toast).
- `EmailLogController` — paginated list + status filter + search.
- `PartnerEmailSendController` / `PartnerEmailLogController` — same, but the
  recipient query is partner-scoped; partner logs filtered to
  `sender_user_id = auth()->id()`.

### Views

- `admin/email-templates/index.blade.php` — `pc-header` + `pc-table` pattern
  (name, subject, created_by, actions), empty state.
- `admin/email-templates/create.blade.php` / `edit.blade.php` — existing
  `editor-card` form pattern (name, subject, body textarea with
  placeholder hint, save/cancel).
- `admin/email-compose.blade.php` — template select (populates subject/body
  via a small inline script), subject, body textarea, recipient group radio +
  user multi-select + newsletter toggle, recipient count, preview box, Send
  button.
- `admin/email-logs.blade.php` — `pc-table`: sent at, sender, recipient,
  subject, status chip (mirror `statusChip()` pattern), batch link.
- `partner/email-compose.blade.php` — same minus groups (buyers list
  checkboxes), no template editing.
- `partner/email-logs.blade.php` — own logs only.

No inline `style=` anywhere; reuse `pc-*`, `btn`, `form-*` classes.

### Mailable

`PlatformMail extends Mailable` — `markdown('emailcenter::mail.platform')`,
`subject($subject)`, `with(['body' => Str::markdown($bodyMarkdown),
'name' => $recipientName])`; blade mirrors `contact.blade.php` mail view
(branded layout, safe `{!! $body !!}` inside the layout's content area — the
existing contact mail already renders markdown the same way; confirm and
mirror).

### Seeding

`EmailTemplateSeeder` — three templates (Newsletter/Announcement, General
Notice, Order update), `created_by = null`, upsert by name so re-seeding is
idempotent. Wire into `DatabaseSeeder` if it lists module seeders, else into
the module's own seeder registration.

### Placeholder rule

Only `{name}` and `{email}` are replaced (str_replace). Unknown braces are
left as-is. Replacement happens per recipient at send time; the log stores the
resolved snapshot.

## Interfaces

### New module `Modules/EmailCenter`

- `Modules\EmailCenter\Providers\EmailCenterServiceProvider`
- `Modules\EmailCenter\app/Http/Controllers/{EmailTemplateController,EmailSendController,EmailLogController,PartnerEmailSendController,PartnerEmailLogController}.php`
- `Modules\EmailCenter\app/Http/Requests/{StoreTemplateRequest,UpdateTemplateRequest,SendEmailRequest}.php`
- `Modules\EmailCenter\app/Mail/PlatformMail.php`
- `Modules\EmailCenter\app/Models/{EmailTemplate,EmailLog}.php`
- `Modules\EmailCenter\app/Services/RecipientResolver.php`
- `Modules\EmailCenter\database/migrations/{2026_08_20_200001_create_email_templates_table,2026_08_20_200002_create_email_logs_table}.php`
- `Modules\EmailCenter\database/seeders/EmailTemplateSeeder.php`
- `Modules\EmailCenter\routes/web.php`
- `Modules\EmailCenter\resources/views/{admin/email-templates/*,admin/email-compose.blade.php,admin/email-logs.blade.php,partner/email-compose.blade.php,partner/email-logs.blade.php,mail/platform.blade.php}.php`
- `Modules/EmailCenter/tests/Feature/{EmailTemplateCrudTest,AdminSendTest,PartnerSendTest,EmailLogTest}.php`

### Consumed (existing, no changes)

- `Modules\PartnerHub\Models\Partner::orders()` pattern for buyer resolution
  (`Order::whereHas('items.product.partners', ...)`).
- The `auth+admin` / `auth+partner` middleware pattern (status gates arrive
  with sweep task E1).
- Existing `pc-*`/`btn`/`form-*` blade classes, `editor-card` form pattern,
  `statusChip()`-style chips, mail layout of `ContactMessageMail`.

### Navigation

- Admin nav (in `app-layout.php` admin section): "Email" links → Templates,
  Compose, Logs (icon + label matching the existing nav items).
- Partner nav: "Email Center" → Compose, Logs.

## Constraints

- Route names above are frozen once created.
- No inline `style=` in any blade.
- Local `QUEUE_CONNECTION=sync` → queued mail sends immediately in dev;
  prod uses the database worker (already running) — no worker changes.
- Conventional commits; full suite must stay green (currently 113 tests /
  401 assertions; this module adds ~20).
- Module registration follows the existing module pattern
  (composer `autoload` / `ModuleServiceProvider` registration — mirror the
  smallest existing module's wiring).
- Recipient search: users where `status = 'active'` and
  `email_verified_at` is not null.

## Testing plan

- `EmailTemplateCrudTest`: create/update/delete via admin; validation
  failures (empty name/subject/body, duplicate name); delete with existing
  logs keeps logs (FK nulls `template_id`).
- `AdminSendTest` (`Mail::fake`): group send (newsletter group respects
  `newsletter_optin`), individual multi-select send, placeholder replacement
  asserted via `Mail::assertSent` content, log rows written with `pending`,
  cap: 101 recipients → validation error, guest/user (non-admin) → 403.
- `PartnerSendTest`: partner sends to own buyer → success + log; partner
  attempts non-buyer email (crafted user id list) → rejected (only buyers are
  accepted); partner without buyers → no recipients option; non-partner → 403.
- `EmailLogTest`: list paginated, status filter, search, partner sees own
  logs only, admin sees all.

## Doc updates

- `docs/AUDIT-2026-08-20-full-app-sweep.md` — new section for the Email
  Center (post-ship).
- `PROJECT_REPORT.txt` — §19 (after sweep §18).
- `docs/PROJECT_ARCHITECTURE.md` — module list, new routes, test totals.

## Sequencing

Spec → plan (writing-plans) → executed via subagent-driven development after
(or interleaved with) sweep tasks T1–T12; the Email Center depends on the
E1 middleware gates (routes use them) but not on earlier tasks, so it can run
as an independent sub-project.