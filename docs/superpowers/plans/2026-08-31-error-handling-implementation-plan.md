# Error Handling Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement module-specific exception handlers for the SmartShop modular monolith, ensuring customers/partners never see SQL errors while logging all errors to dedicated per-module log files. Admins retain full debugging access via existing `laravel.log`.

**Architecture:** Four module-specific Exception handlers (IdentityAccess, MarketplacePipeline, CatalogDelivery, PartnerHub) each with their own report() and render() methods. A delegation middleware (ModuleExceptionHandler) routes requests to the correct module handler based on URL path. Four dedicated log channels (identity-errors, pipeline-errors, catalog-errors, partner-errors) alongside existing laravel.log. Error views per module styled to each module's design system.

**Tech Stack:** Laravel 11+, PHP 8.2+, Monolog, Laravel logging channels, Blade views, middleware kernel.

---

## Global Constraints (from spec)

- Zero database changes — log files only
- No inline `style=` attributes
- Conventional commits + targeted `git add`
- `QUEUE_CONNECTION=sync` locally; `QUEUE_CONNECTION=database` on prod
- Full suite **203 tests / 854 assertions** must remain passing
- `composer dump-autoload` needed after new module files
- `php artisan module:discover` after module additions
- Delete `bootstrap/cache/modules.php` after module changes
- Do NOT clear the online database — it has real working data
- Route names immutable
- Production deploy: `php artisan serve --port=8001` → SSH → backup → `git stash push modules_statuses.json` → `git pull` → `git stash pop` → `php artisan migrate --force` → `composer dump-autoload` (use `COMPOSER_ALLOW_SUPERUSER=1` for new modules) → `php artisan package:discover` → `npm run build` → route/config/view cache clear → `supervisorctl restart smartshop-worker:`
- **Do NOT set `QUEUE_CONNECTION=database` locally** — no queue worker running, mail would pile up

---

## Overview of Tasks

This plan implements module-specific exception handling across 4 modules. Each task is bite-sized and independently testable. Tasks build on each other — do not skip ahead.

**Total estimated time:** 45-60 minutes across all tasks  
**Test verification:** Run `php artisan test` after all tasks; should show 203 passed

---

### Task 1: Add 4 New Log Channels to config/logging.php

**Files to modify:** `config/logging.php`

**Create 4 new log channels** alongside existing channels:

- `identity-errors` → `storage/logs/identity-errors.log`
- `pipeline-errors` → `storage/logs/pipeline-errors.log`
- `catalog-errors` → `storage/logs/catalog-errors.log`
- `partner-errors` → `storage/logs/partner-errors.log`

Each channel uses `driver => 'single'`, `formatter => LineFormatter`, and appropriate `path`.

**Step 1.1:** Open `config/logging.php` and add the 4 new channel definitions under the `channels` key, preserving existing channels.

**Step 1.2:** Run `php artisan config:clear` to reload config.

**Step 1.3:** Verify with `php artisan tinker --execute="\Log::channel('identity-errors')->info('test message');"` — check file `storage/logs/identity-errors.log` exists with test message.

**Step 1.4:** Commit: `git add config/logging.php && git commit -m "feat: add 4 module-specific log channels to logging config"`

---

### Task 2: Create Module Exception Handlers (4 files)

**Files to create:**
- `Modules/IdentityAccess/Exceptions/Handler.php`
- `Modules/MarketplacePipeline/Exceptions/Handler.php`
- `Modules/CatalogDelivery/Exceptions/Handler.php`
- `Modules/PartnerHub/Exceptions/Handler.php`

**Each handler:**
- Extends `Illuminate\Foundation\Exceptions\Handler`
- Overrides `report(\Exception $e, Request $request)` — logs to module's dedicated channel based on user role (guest/partner/admin)
- Overrides `render($request, \Exception $e)` — serves module's error views based on exception type

**Step 2.1:** Create `Modules/IdentityAccess/Exceptions/Handler.php` — report logs to `identity-errors` channel; render serves `identity::errors.*` views.

**Step 2.2:** Create `Modules/MarketplacePipeline/Exceptions/Handler.php` — report logs to `pipeline-errors` channel; render serves `marketplacepipeline::errors.*` views.

**Step 2.3:** Create `Modules/CatalogDelivery/Exceptions/Handler.php` — report logs to `catalog-errors` channel; render serves `catalogdelivery::errors.*` views.

**Step 2.4:** Create `Modules/PartnerHub/Exceptions/Handler.php` — report logs to `partner-errors` channel; render serves `partner::errors.*` views.

**Step 2.5:** For each handler, write a failing test that calls `report()` and verifies the correct log channel is used.

**Step 2.6:** Run each failing test — should FAIL (handler not yet wired).

**Step 2.7:** Verify render() returns the correct view names for 500, 404, ValidationException.

**Step 2.8:** Commit each file separately with descriptive messages.

---

### Task 3: Create Delegation Middleware

**Files to create:** `app/Http/Middleware/ModuleExceptionHandler.php`

**Step 3.1:** Create the middleware that determines which module handles the request based on URL path:
- `/partner/...` → `Modules\PartnerHub\Exceptions\Handler`
- `/admin/...` or `/identity/...` → `Modules\IdentityAccess\Exceptions\Handler`
- `/shop/...` / `/product/...` / `/collection/...` → `Modules\CatalogDelivery\Exceptions\Handler`
- `/checkout/...` / `/order/...` → `Modules\MarketplacePipeline\Exceptions\Handler`
- Default → `null` (fall back to base app Handler)

**Step 3.2:** The `handle()` method delegates to the found handler's `handle()` and `render()` methods.

**Step 3.3:** Register the middleware in `app/Http/Kernel.php` — add to `$middleware` array.

**Step 3.4:** Run `php artisan route:clear` and `php artisan view:clear`.

**Step 3.5:** Test manually: trigger an error on `/shop` route and verify it goes to `CatalogDelivery` handler.

**Step 3.6:** Commit: `git add app/Http/Middleware/ModuleExceptionHandler.php && git commit -m "feat: add module exception handler middleware"`

---

### Task 4: Create Error Views Per Module

**Files to create** (4 modules × 4 views = 16 files):

For each module: `resources/views/modules/{ModuleName}/errors/500.blade.php`, `404.blade.php`, `validation.blade.php`, `generic.blade.php`

**Design language** (per module's existing CSS vars):
- Primary: `#0f172a` / `#f8fafc`
- Accent: `#3b82f6`
- No SQL/stack trace exposed
- CTA: "Return to Shop" / "Try Again"
- Theme-aware dark/light mode

**Step 4.1:** Create directory `resources/views/modules/catalogdelivery/errors/`

**Step 4.2:** Create `catalogdelivery::errors.500.blade.php` — friendly "something went wrong" message.

**Step 4.3:** Create `catalogdelivery::errors.404.blade.php` — "page not found" (may reuse existing, but module-specific version preferred).

**Step 4.4:** Create `catalogdelivery::errors.validation.blade.php` — validation error summary.

**Step 4.5:** Create `catalogdelivery::errors.generic.blade.php` — fallback for unexpected errors.

**Step 4.6:** Repeat Steps 4.1-4.5 for 3 other modules:
- `Modules/IdentityAccess/resources/views/errors/`
- `Modules/MarketplacePipeline/resources/views/errors/`
- `Modules/PartnerHub/resources/views/errors/`

**Step 4.7:** Test each view renders without errors: `php artisan view:compose catalogdelivery::errors.500 ...`

**Step 4.8:** Commit all view files with one commit: `git add resources/views/modules/... && git commit -m "feat: add module error views across 4 modules"`

---

### Task 5: Verify Full Integration

**Step 5.1:** Run the full test suite: `php artisan test`

**Step 5.2:** Verify 203 passed, 854 assertions — no regressions.

**Step 5.3:** Verify log files are created when errors are triggered:
- Trigger an error on `/shop` → check `storage/logs/catalog-errors.log`
- Trigger an error on partner route → check `storage/logs/partner-errors.log`
- Trigger an error as admin → check `storage/logs/laravel.log` still works

**Step 5.4:** Manually verify customers see friendly messages, not SQL.

**Step 5.5:** Commit any final changes: `git add ... && git commit -m "fix: verify error handling integration"`

**Step 5.6:** Final status: `git status` should show clean working tree (all changes committed).

---

## Task Dependencies

| Task | Depends On |
|------|------------|
| Task 1 (log channels) | None — foundational |
| Task 2 (4 exception handlers) | Task 1 — needs log channels defined |
| Task 3 (middleware) | Task 2 — needs handlers created |
| Task 4 (error views) | Task 2 — needs handler render() targets |
| Task 5 (verify integration) | Tasks 1-4 — full integration |

---

## Success Criteria Checklist

- [ ] 4 new log channels defined in `config/logging.php`
- [ ] 4 module-specific Exception handlers created with report() and render()
- [ ] Delegation middleware registered in Kernel
- [ ] 16 error views created across 4 modules
- [ ] All 203 existing tests pass (854 assertions)
- [ ] Manual testing: guests see friendly messages, no SQL visible
- [ ] Manual testing: partners see friendly messages appropriate to context
- [ ] Manual testing: admins still see detailed logs in `laravel.log`
- [ ] Log files written to correct channels (identity-errors, pipeline-errors, catalog-errors, partner-errors)
- [ ] Middleware correctly routes based on URL path
- [ ] `php artisan test` green after all changes
- [ ] No database data cleared at any point

---

## Subagent Execution (Recommended)

If executing via subagents, use `superpowers:subagent-driven-development`:

- **One task per subagent** — each agent handles one task from above
- **Review between tasks** — verify task completion before starting next
- **Fast iteration** — agents work in parallel where independent

If executing inline in this session, use `superpowers:executing-plans` with checkpoint reviews after each task group.

---

## Plan Document Location

Saved to: `docs/superpowers/plans/2026-08-31-error-handling-implementation-plan.md`

**Plan complete and saved.** Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach would you like?** Please confirm, and I'll proceed accordingly.