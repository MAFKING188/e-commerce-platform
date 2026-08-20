# Email Center — Implementation Plan

**Spec:** `docs/superpowers/specs/2026-08-20-email-center-design.md` (approved)
**Execution:** subagent-driven development
**Baseline:** sweep plan Tasks 1–12 run in parallel; Email Center routes rely on the E1 middleware status gates — **Task 4 of the sweep plan must land first** (AdminMiddleware/PartnerMiddleware gain the `status === 'active'` check). Tasks below assume that.

## Conventions

- New module `Modules/EmailCenter` (nwidart pattern: `module.json`, `composer.json`, `app/Providers/{EmailCenterServiceProvider,RouteServiceProvider}.php`, `app/{Http/Controllers,Http/Requests,Mail,Models,Services}/`, `database/{migrations,seeders}/`, `resources/views/`, `routes/web.php`, `tests/Feature/`).
- After creating the module: `composer dump-autoload` (root composer.json `include: Modules/*/composer.json` picks up the new PSR-4) + `php artisan package:discover` (regenerates `bootstrap/cache/modules.php`). Verify with `php artisan route:list | grep email`.
- Markdown mailable: `markdown: 'emailcenter::emails.platform'`, `<x-mail::message>` layout (mirror `Modules/CatalogDelivery/resources/views/emails/contact-message.blade.php`), body rendered via `{!! Str::markdown($body) !!}` where `$body` = resolved markdown passed in.
- Blades reuse `pc-*`, `btn`, `form-*`, `editor-card`, `pc-table`, `pc-header`, `pc-wrap-narrow`, `pc-field` classes; `@include('partials.admin-nav')` / `@include('partials.partner-nav')`; no inline `style=`; `@section('title', ...)`.
- Local queue is `sync` → queued mail sends inline in tests/dev. Tests use `Mail::fake()`.
- Every task: TDD (test → red → green) then `php artisan test` full suite green, then a conventional-commit with targeted `git add`.

---

### Task 1: Module skeleton + registration

**Files (create):**
- `Modules/EmailCenter/module.json` — `{"name": "EmailCenter", "alias": "emailcenter", "providers": ["Modules\\EmailCenter\\Providers\\EmailCenterServiceProvider"], "files": []}`
- `Modules/EmailCenter/composer.json` — mirror `Modules/TelemetryPipeline/composer.json` (PSR-4 `Modules\EmailCenter\` → `app/`, `Database\Factories\` → `database/factories/`, `Database\Seeders\` → `database/seeders/`, dev `Tests\` → `tests/`).
- `Modules/EmailCenter/app/Providers/EmailCenterServiceProvider.php` — extends `Nwidart\Modules\Support\ModuleServiceProvider`, `$name = 'EmailCenter'`, `$nameLower = 'emailcenter'`, `$providers = [RouteServiceProvider::class]`.
- `Modules/EmailCenter/app/Providers/RouteServiceProvider.php` — mirror `Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php` (mapWebRoutes with `module_path($this->name, '/routes/web.php')`); register RateLimiter `email` → `Limit::perMinute(5)` (used by the send route via `throttle:email`).
- `Modules/EmailCenter/routes/web.php` — empty placeholder for now.

- [ ] **Step 1:** Create the files above.
- [ ] **Step 2:** `composer dump-autoload && php artisan package:discover`
- [ ] **Step 3:** Verify: `php artisan route:list` runs clean; `ls bootstrap/cache/modules.php` contains `EmailCenterServiceProvider`.
- [ ] **Step 4:** `php artisan test` — baseline green (113 tests).
- [ ] **Step 5: Commit**
```bash
git add Modules/EmailCenter
git commit -m "feat(emailcenter): module skeleton and registration"
```

---

### Task 2: Migrations + models + seeder

**Files (create):**
- `Modules/EmailCenter/database/migrations/2026_08_20_200001_create_email_templates_table.php`
- `Modules/EmailCenter/database/migrations/2026_08_20_200002_create_email_logs_table.php`
- `Modules/EmailCenter/app/Models/EmailTemplate.php`
- `Modules/EmailCenter/app/Models/EmailLog.php`
- `Modules/EmailCenter/database/seeders/EmailTemplateSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (append `EmailTemplateSeeder::class`)

**Schema (per spec):**
- `email_templates`: id, name string(100) unique, subject string(150), body_markdown text, created_by FK users id nullable `nullOnDelete()`, timestamps.
- `email_logs`: id, batch_id uuid, sender_user_id FK users id, sender_role string(20), recipient_email string(255), template_id FK email_templates id nullable `nullOnDelete()`, subject string(150), body_markdown text, status string(10) default `pending`, error text nullable, timestamps. Indexes: batch_id, sender_user_id, status.

**Models:**
- `EmailTemplate`: `$fillable = ['name','subject','body_markdown','created_by']`; `creator()` belongsTo User.
- `EmailLog`: `$fillable = ['batch_id','sender_user_id','sender_role','recipient_email','template_id','subject','body_markdown','status','error']`; `sender()` belongsTo User; `template()` belongsTo EmailTemplate; `$casts = ['batch_id' => 'string']`.

**Seeder:** 3 templates (name, subject, body_markdown) — "Newsletter — LUWI Digest" / "Notice — Order Update" / "Notice — General Announcement"; `created_by = null`; `updateOrCreate(['name' => ...])` idempotent.

- [ ] **Step 1:** Write migrations + models + seeder, wire into DatabaseSeeder.
- [ ] **Step 2:** `php artisan migrate` + `php artisan db:seed --class="Modules\EmailCenter\Database\Seeders\EmailTemplateSeeder"` — verify 3 rows.
- [ ] **Step 3:** `php artisan test` — green.
- [ ] **Step 4: Commit**
```bash
git add Modules/EmailCenter/database Modules/EmailCenter/app/Models database/seeders/DatabaseSeeder.php
git commit -m "feat(emailcenter): templates and logs tables, models, starter templates"
```

---

### Task 3: RecipientResolver + PlatformMail (unit-tested core)

**Files (create):**
- `Modules/EmailCenter/app/Services/RecipientResolver.php`
- `Modules/EmailCenter/app/Mail/PlatformMail.php`
- `Modules/EmailCenter/resources/views/emails/platform.blade.php`
- `Modules/EmailCenter/tests/Unit/RecipientResolverTest.php`
- `Modules/EmailCenter/tests/Feature/PlatformMailRenderTest.php`

**RecipientResolver API:**
- `static function resolveForAdmin(string $group, ?array $userIds = null, bool $newsletterOnly = false): Collection` — groups: `all` (active+verified users), `admins`, `partners`, `members` (role `user`), `newsletter` (active+verified+`newsletter_optin=true`); `userIds` filters individual picks (also active+verified only). Returns `User` models.
- `static function resolveForPartner(int $partnerUserId): Collection` — distinct users of orders via `Order::whereHas('items.product.partners', fn($q) => $q->where('partners.id', $partner->id))` joined with active+verified filter. Partner `id` = the Partner record for the user (`Partner::where('user_id', $partnerUserId)->first()`); return empty if no Partner record.
- `static function replacePlaceholders(string $text, User $user): string` — `str_replace(['{name}','{email}'], [$user->name, $user->email], $text)`.

**PlatformMail:** `extends Mailable implements ShouldQueue`; ctor `(string $subject, string $bodyMarkdown, string $recipientName)`; `envelope(): subject`; `content(): markdown 'emailcenter::emails.platform'` with `['body' => Str::markdown($bodyMarkdown), 'name' => $recipientName]`. Blade:
```blade
<x-mail::message>
{!! $body !!}

Regards,<br>
The SmartShop Team
</x-mail::message>
```
(`{!! !!}` renders pre-rendered markdown HTML inside the mail layout — the layout is HTML-based so raw markdown would double-escape; render once in the mailable.)

**Tests:**
- `RecipientResolverTest`: `newsletter` group excludes opted-out users; `members` group = role `user` only; individual `userIds` excludes inactive/unverified; `resolveForPartner` returns only actual buyers; empty when partner has no orders; `replacePlaceholders` swaps `{name}`/`{email}` and leaves unknown braces intact.
- `PlatformMailRenderTest`: `Mail::fake()` — `Mail::to($user)->send(new PlatformMail('Hi {name}', '**bold** text', 'Ada'))`; `Mail::assertSent(PlatformMail::class, fn($m) => str_contains($m->bodyMarkdown, '**bold**'))`; assert subject untouched.

- [ ] **Step 1:** Write tests → run → red.
- [ ] **Step 2:** Implement resolver + mailable + blade → tests green.
- [ ] **Step 3:** `php artisan test` full suite green.
- [ ] **Step 4: Commit**
```bash
git add Modules/EmailCenter/app/Services Modules/EmailCenter/app/Mail Modules/EmailCenter/resources/views/emails Modules/EmailCenter/tests
git commit -m "feat(emailcenter): recipient resolver and platform mailable"
```

---

### Task 4: Admin template CRUD

**Files (create):**
- `Modules/EmailCenter/app/Http/Controllers/EmailTemplateController.php`
- `Modules/EmailCenter/app/Http/Requests/StoreTemplateRequest.php`
- `Modules/EmailCenter/app/Http/Requests/UpdateTemplateRequest.php`
- `Modules/EmailCenter/resources/views/admin/email-templates/index.blade.php`
- `Modules/EmailCenter/resources/views/admin/email-templates/create.blade.php`
- `Modules/EmailCenter/resources/views/admin/email-templates/edit.blade.php`
- Modify: `Modules/EmailCenter/routes/web.php`
- Modify: `resources/views/partials/admin-nav.blade.php`
- Create: `Modules/EmailCenter/tests/Feature/EmailTemplateCrudTest.php`

**Routes (in web.php, admin group):**
```php
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('email-templates/create', [EmailTemplateController::class, 'create'])->name('email-templates.create');
    Route::post('email-templates', [EmailTemplateController::class, 'store'])->name('email-templates.store');
    Route::get('email-templates/{id}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::put('email-templates/{id}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::delete('email-templates/{id}', [EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');
});
```

**Controller:** standard resource minus `show`; `store` sets `created_by = auth()->id()`; `destroy` relies on `nullOnDelete()` FK (logs keep history) then redirects with status toast. Validation: name required|string|max:100|unique; subject required|max:150; body_markdown required|string.

**Blades:**
- `index`: `pc-header` (eyebrow "Messaging", title "Email Templates", Create button) + `pc-table` (Name, Subject, Created by, Actions edit/delete with `data-confirm` delete form — mirror `admin/categories/index.blade.php` delete pattern) + empty state (`@if($templates->isEmpty())` block mirroring `admin/reviews/index.blade.php`).
- `create`/`edit`: `pc-wrap-narrow` + `pc-card` form (mirror `admin/categories/create.blade.php`): name input, subject input, body textarea (with placeholder hint text listing `{name}` `{email}`), `pc-form-actions` Save/Cancel.

**Nav:** `resources/views/partials/admin-nav.blade.php` — add after Contacts link:
```blade
<a href="{{ route('admin.email-templates.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.email-*') ? 'is-active' : '' }}">Email Center</a>
```

**Tests (`EmailTemplateCrudTest`, RefreshDatabase + actingAs admin):** index 200 shows templates; store creates + redirects; validation errors (empty fields, duplicate name); edit renders; update persists; destroy deletes + keeps existing log rows (`template_id` nulled — create a log row first, assert it survives).

- [ ] **Step 1:** Tests → red.
- [ ] **Step 2:** Routes + controller + requests + blades + nav → green.
- [ ] **Step 3:** `php artisan test` full green.
- [ ] **Step 4: Commit**
```bash
git add Modules/EmailCenter/app/Http Modules/EmailCenter/resources/views/admin Modules/EmailCenter/routes Modules/EmailCenter/tests resources/views/partials/admin-nav.blade.php
git commit -m "feat(emailcenter): admin email template CRUD"
```

---

### Task 5: Admin compose + send (queued, logged)

**Files (create):**
- `Modules/EmailCenter/app/Http/Controllers/EmailSendController.php`
- `Modules/EmailCenter/app/Http/Requests/SendEmailRequest.php`
- `Modules/EmailCenter/resources/views/admin/email-compose.blade.php`
- Modify: `Modules/EmailCenter/routes/web.php` (compose + send routes)
- Create: `Modules/EmailCenter/tests/Feature/AdminSendTest.php`

**Routes:**
```php
Route::get('email-compose', [EmailSendController::class, 'compose'])->name('email.compose');
Route::post('email-send', [EmailSendController::class, 'send'])->middleware('throttle:email')->name('email.send');
Route::get('users/search', [EmailSendController::class, 'searchUsers'])->name('users.search');
```

**compose():**
- loads templates for the select; groups list; returns view. Data: `$templates`, `$groups = ['all' => 'All active users', 'admins' => 'Administrators', 'partners' => 'Partners', 'members' => 'Members', 'newsletter' => 'Newsletter subscribers']`, `$userSearchUrl` = `route('admin.users.search')`.

**searchUsers(Request $request):** returns JSON of
`User::where('status','active')->whereNotNull('email_verified_at')->where(fn($q) => $q->where('name','like',"%q%")->orWhere('email','like',"%q%"))->limit(10)->get(['id','name','email'])` — used by the compose page's remote multi-select.

**send():**
1. `SendEmailRequest` rules: subject required|max:150; body required|max:10000; `group` in:all,admins,partners,members,newsletter nullable; `user_ids` array|max:100 nullable; recipients present (at least one of group/user_ids); if `user_ids` given → `user_ids.*` exists:users,id.
2. `$recipients = $user_ids ? RecipientResolver::resolveForAdmin(null, $user_ids) : RecipientResolver::resolveForAdmin($group)`; `$recipients = $newsletter_only ? $recipients->filter(newsletter_optin) : $recipients`; cap: `abort(422)` if count > 100 (validated via `user_ids.max:100` + group resolution check → validation error `recipients` too many).
3. `$batchId = Str::uuid()`; per recipient: resolve placeholders → `EmailLog::create([...status: 'pending'...])` → `Mail::to($recipient->email)->queue(new PlatformMail($subject, $resolvedBody, $recipient->name))`; **on dispatch success** update the row to `sent`; on a dispatch exception, update to `failed` with the error message and continue the batch (no 500).
4. Redirect back with `status` toast "Queued N email(s) to M recipient(s)".

**Blade `email-compose`:** `pc-header` (Messaging / "Compose & Send") + `pc-card` form: template select (JS: on change, if user hasn't typed, fill subject/body from template data JSON passed from controller), subject input, body textarea, recipient section — radio group picker + checkbox "Only newsletter subscribers" + multi-select user search (datalist or search input with results list; keep simple: a select multiple with remote-loaded options via the search endpoint), recipient count span, Send button (`pc-form-actions`). Placeholder hint text. No inline styles; the JS uses a small inline `<script>` (allowed — page script, not element styles).

**Tests (`AdminSendTest`, Mail::fake):** group `newsletter` sends only to opted-in active users and writes one log row per recipient with matching batch_id; individual user_ids send works; placeholder replacement asserted via `Mail::assertSent(PlatformMail::class, fn($m) => str_contains($m->bodyMarkdown, $user->name))`; cap: 101 user_ids → validation error; non-admin user → 403; guest → redirect login.

- [ ] **Step 1:** Tests → red.
- [ ] **Step 2:** Implement → green.
- [ ] **Step 3:** `php artisan test` full green.
- [ ] **Step 4: Commit**
```bash
git add Modules/EmailCenter/app/Http/Controllers/EmailSendController.php Modules/EmailCenter/app/Http/Requests/SendEmailRequest.php Modules/EmailCenter/resources/views/admin/email-compose.blade.php Modules/EmailCenter/routes/web.php Modules/EmailCenter/tests
git commit -m "feat(emailcenter): admin compose and queued send with logging"
```

---

### Task 6: Admin send history + partner center

**Files (create):**
- `Modules/EmailCenter/app/Http/Controllers/EmailLogController.php`
- `Modules/EmailCenter/app/Http/Controllers/PartnerEmailSendController.php`
- `Modules/EmailCenter/app/Http/Controllers/PartnerEmailLogController.php`
- `Modules/EmailCenter/resources/views/admin/email-logs.blade.php`
- `Modules/EmailCenter/resources/views/partner/email-compose.blade.php`
- `Modules/EmailCenter/resources/views/partner/email-logs.blade.php`
- Modify: `Modules/EmailCenter/routes/web.php` (logs route + partner group)
- Modify: `resources/views/partials/partner-nav.blade.php`
- Create: `Modules/EmailCenter/tests/Feature/PartnerSendTest.php`, `Modules/EmailCenter/tests/Feature/EmailLogTest.php`

**Routes (append):**
```php
Route::get('email-logs', [EmailLogController::class, 'index'])->name('email.logs');

Route::prefix('partner')->middleware(['auth', 'partner'])->name('partner.')->group(function () {
    Route::get('email-compose', [PartnerEmailSendController::class, 'compose'])->name('email.compose');
    Route::post('email-send', [PartnerEmailSendController::class, 'send'])->middleware('throttle:email')->name('email.send');
    Route::get('email-logs', [PartnerEmailLogController::class, 'index'])->name('email.logs');
});
```

**EmailLogController@index:** paginated 15, optional `?status=` filter and `?q=` search (recipient_email/subject like), newest first. Blade `admin/email-logs.blade.php`: `pc-header` (Messaging / "Send History") + filter bar (`pc-table` style: Sent at, Sender, Recipient, Subject, Status chip via `@if` classes — mirror `statusChip()`-ish pattern used in admin users index), empty state, `{{ $logs->links() }}`.

**PartnerEmailSendController:**
- `compose()`: `$buyers = RecipientResolver::resolveForPartner(auth()->id())`; `$templates` (read-only select). Blade mirrors admin compose minus groups/newsletter toggle; buyer list as checkboxes with count; if `$buyers->isEmpty()` show an info box "You have no buyers yet — emails will be available after your first orders." and no send form.
- `send()`: same rules minus group; recipients = checked buyer ids → `resolveForPartner(auth()->id())->whereIn('id', $ids)` (intersect — non-buyer ids are silently dropped, never sent); batch + logs + queue, same as admin. Sender role snapshot `partner`.

**PartnerEmailLogController@index:** `EmailLog::where('sender_user_id', auth()->id())` paginated, same UI minus filters.

**Nav:** `resources/views/partials/partner-nav.blade.php` — after Profile link:
```blade
<a href="{{ route('partner.email.compose') }}" class="pc-nav__tab {{ request()->routeIs('partner.email.*') ? 'is-active' : '' }}">Email Center</a>
```

**Tests:**
- `PartnerSendTest`: partner with buyer → send to buyer id → log row (sender_role `partner`) + `Mail::assertSent`; partner posts a non-buyer user id → that user never receives mail (assert `Mail::assertNothingSent` when only non-buyer ids given); partner with no orders → compose shows no buyers (assert view has empty-state text); non-partner → 403.
- `EmailLogTest`: admin sees all logs (two senders), partner sees own only; status filter; search by subject; pagination present.

- [ ] **Step 1:** Tests → red.
- [ ] **Step 2:** Implement controllers + blades + routes + nav → green.
- [ ] **Step 3:** `php artisan test` full green.
- [ ] **Step 4: Commit**
```bash
git add Modules/EmailCenter/app/Http/Controllers Modules/EmailCenter/resources/views/admin/email-logs.blade.php Modules/EmailCenter/resources/views/partner Modules/EmailCenter/routes/web.php Modules/EmailCenter/tests resources/views/partials/partner-nav.blade.php
git commit -m "feat(emailcenter): send history and partner email center"
```

---

### Task 7: Docs, deploy, live verification

**Files (modify):**
- `docs/AUDIT-2026-08-20-full-app-sweep.md` — new "Email Center (shipped 2026-08-20)" section summarizing module, routes, tests.
- `PROJECT_REPORT.txt` — add §19 (Email Center: what it does, module layout, tests count).
- `docs/PROJECT_ARCHITECTURE.md` — add `Modules/EmailCenter` to module list, new routes table entries, updated test totals.

- [ ] **Step 1:** Update docs. `php artisan test` full green (expect baseline 113 + sweep additions + ~20 email tests).
- [ ] **Step 2: Commit + push**
```bash
git add docs/AUDIT-2026-08-20-full-app-sweep.md PROJECT_REPORT.txt docs/PROJECT_ARCHITECTURE.md
git commit -m "docs: Email Center — audit, report §19, architecture"
git push
```
- [ ] **Step 3: Deploy** `ssh root@104.248.163.215 "cd /var/www/smartshop && git pull -q && php artisan migrate --force && php artisan route:clear -q && php artisan config:clear -q && php artisan queue:restart"`
- [ ] **Step 4: Live verify prod**
  - `/admin/email-templates` (login mafuletil@gmail.com) → 3 seeded templates, create/edit/delete works.
  - `/admin/email-compose` → pick Newsletter template, send to "All active users" (or newsletter group), toast shows count; a test recipient inbox receives the branded email; `/admin/email-logs` shows the batch `sent` rows.
  - `/partner/email-compose` as a partner user → buyer list; a partner without orders sees the empty-state hint.
  - Spot-check nav links on admin + partner pages.