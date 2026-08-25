# Error Handling — Module-Specific Exception Handlers

**Date:** 2026-08-31
**Author:** Development Team
**Modular Monolith:** SmartShop/LUWI e-commerce platform
**Status:** Approached designed — pending implementation plan

---

## 1. Overview

This design implements **module-specific exception handlers** for the SmartShop modular monolith architecture. Each module owns its error handling behavior, dedicated log files, and module-styled error views. This provides clean separation of concerns and matches the existing module structure.

### 1.1. Goals

- Customers never see SQL errors or stack traces — only friendly messages
- Partners in the vendor portal see friendly messages appropriate to their context
- Admins retain full debugging access via existing log infrastructure
- All errors are logged to dedicated per-module log files for easy auditing
- Zero database changes — log files only
- Maintains backward compatibility with existing `laravel.log`

### 1.2. Non-Goals

- Real-time error notification (email/SMS on errors)
- Error rate alerting / Sentry integration
- Automatic error categorization beyond module-level separation

---

## 2. Module-Specific Exception Handlers

Each of the 4 core modules creates its own `Exceptions/Handler.php` extending Laravel's base Handler. Each handler is responsible for:

- **`report($exception)`**: Detect the user context and log to the module's dedicated log channel
- **`render($request, $exception)`**: Intercept and serve the module's custom error views

### 2.1. Module Handlers

| Module | Handler Path | Log Channel | Primary Audience |
|--------|-------------|-------------|------------------|
| `IdentityAccess` | `Modules/IdentityAccess/Exceptions/Handler.php` | `identity-errors.log` | Partners + Admins |
| `MarketplacePipeline` | `Modules/MarketplacePipeline/Exceptions/Handler.php` | `pipeline-errors.log` | Buyers + Partners |
| `CatalogDelivery` | `Modules/CatalogDelivery/Exceptions/Handler.php` | `catalog-errors.log` | Buyers |
| `PartnerHub` | `Modules/PartnerHub/Exceptions/Handler.php` | `partner-errors.log` | Partners |

### 2.2. report() Method

Each module's `report()` method:

1. Determines the user role from `auth()->user()`:
   - `null` → guest shopper
   - `role === 'partner'` → vendor portal user
   - `role === 'admin'` → system administrator
2. Logs to the module's dedicated channel:
   - Guest shoppers → `customer-errors` subset (see logging config)
   - Partners → module's dedicated channel (e.g., `identity-errors.log`)
   - Admins → `laravel.log` (existing, unchanged)
3. Always includes full exception details in the log entry (for admin debugging)

**Example (`Modules/MarketplacePipeline/Exceptions/Handler.php`):**

```php
use Illuminate\Foundation\Exceptions\Handler as Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Handler extends Handler
{
    public function report(\Exception $e, Request $request)
    {
        $user = $request->user();

        // Determine log channel based on user role
        if ($user && $user->role === 'partner') {
            Log::channel('partner-errors')->error(
                "Partner error: {$e->getMessage()}",
                ['exception' => $e, 'user' => $user]
            );
        } elseif ($user && $user->role === 'admin') {
            // Admins: use default channel (laravel.log)
            parent::report($e, $request);
        } else {
            // Guests/shoppers: log to customer-errors
            Log::channel('customer-errors')->error(
                "Shop error: {$e->getMessage()}",
                ['exception' => $e]
            );
        }
    }

    // ...
}
```

### 2.3. render() Method

Each module's `render()` method intercepts exceptions and serves module-styled views:

| Exception Type | View Path | Audience |
|----------------|-----------|----------|
| `HttpException` with status 404 | `modules.catalogdelivery::errors.404` (or module-specific) | All |
| `HttpException` with status 500 | `modules.marketplacepipeline::errors.500` | All |
| `ValidationException` | `modules.catalogdelivery::errors.validation` | All |
| Any other `Exception` | `modules.catalogdelivery::errors.generic` | All |

Views **never** expose:
- SQL query strings
- Stack traces
- File paths
- Database connection details
- Any internal technical information

### 2.4. Example Error Views

**`resources/views/modules/catalogdelivery/errors/500.blade.php`:**

```blade
{{-- Brand: SmartShop dark/light compatible --}}
@php
$isDark = request()->hasAttribute('data-theme') && request()->getAttribute('data-theme') === 'dark';
@endphp

<div class="error-page" style="background: {{ $isDark ? '#0f172a' : '#f8fafc' }}; color: {{ $isDark ? '#f8fafc' : '#0f172a' }}">
    <div class="error-content" style="max-width: 600px; margin: 0 auto; padding: 4rem 2rem;">
        <div class="error-code" style="font-size: 8rem; font-weight: 800; color: #3b82f6; margin-bottom: 1rem;">500</div>
        <h1 style="font-size: 1.5rem; margin-bottom: 1rem; color: inherit;">Something went wrong</h1>
        <p style="font-size: 1rem; color: #64748b; margin-bottom: 2rem;">
            We're experiencing technical difficulties. Please try again later.
        </p>
        <a href="{{ url()->previous() ?? '/shop' }}" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 12px; text-decoration: none;">
            Return to Shop
        </a>
    </div>
</div>
```

**Error views for all 4 modules** follow the same pattern, using each module's established color variables and design system.

---

## 3. Delegation Middleware

Since Laravel normally uses a single application-level Handler, a **middleware** routes incoming requests to the correct module's Handler based on the URL path.

### 3.1. Middleware: `ModuleExceptionHandler`

**File:** `app/Http/Middleware/ModuleExceptionHandler.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as BaseHandler;

class ModuleExceptionHandler
{
    public function handle(Request $request, Closure $next)
    {
        $moduleHandler = $this->findModuleHandler($request);

        if (!$moduleHandler) {
            return $next($request);
        }

        return $moduleHandler->handle($request, function (\Exception $e) use ($moduleHandler) {
            return $moduleHandler->render($request, $e);
        });
    }

    private function findModuleHandler(Request $request)
    {
        $path = $request->path();

        // Partner hub routes
        if (Str::startsWith($path, 'partner')) {
            return app('Modules\PartnerHub\Exceptions\Handler');
        }

        // Admin/identity routes
        if (Str::startsWith($path, 'admin') || Str::startsWith($path, 'identity')) {
            return app('Modules\IdentityAccess\Exceptions\Handler');
        }

        // Shop/catalog routes
        if (Str::startsWith($path, 'shop') || Str::startsWith($path, 'product') || Str::startsWith($path, 'collection')) {
            return app('Modules\CatalogDelivery\Exceptions\Handler');
        }

        // Checkout/order routes
        if (Str::startsWith($path, 'checkout') || Str::startsWith($path, 'order')) {
            return app('Modules\MarketplacePipeline\Exceptions\Handler');
        }

        // Default: fall back to base application Handler
        return null;
    }
}
```

### 3.2. Kernel Registration

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\ModuleExceptionHandler::class,
];
```

Or add to `$middlewareGroups` as appropriate.

---

## 4. Logging Configuration

Update `config/logging.php` to add four dedicated channels:

```php
'channels' => [

    // ... existing channels ...

    'identity-errors' => [
        'driver' => 'single',
        'path' => storage_path('logs/identity-errors.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'formatter' => Monolog\Formatter\LineFormatter::class,
    ],

    'pipeline-errors' => [
        'driver' => 'single',
        'path' => storage_path('logs/pipeline-errors.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'formatter' => Monolog\Formatter\LineFormatter::class,
    ],

    'catalog-errors' => [
        'driver' => 'single',
        'path' => storage_path('logs/catalog-errors.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'formatter' => Monolog\Formatter\LineFormatter::class,
    ],

    'partner-errors' => [
        'driver' => 'single',
        'path' => storage_path('logs/partner-errors.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'formatter' => Monolog\Formatter\LineFormatter::class,
    ],

],
```

### 4.1. Log Entry Format

Each log entry uses `LineFormatter` with this pattern:

```
[{date}] {level:upper} {ip} {message} {context}
```

Example `identity-errors.log` entry:

```
[2026-08-31 14:32:18] ERROR [10.0.0.1] Partner validation failed [{"exception":"ValidationException","message":"The given data was invalid.","user":{"id":8,"role":"partner"}}]
```

---

## 5. Error Views Per Module

Each module creates `resources/views/modules/{ModuleName}/errors/` directory with:

| View | Description |
|------|-------------|
| `500.blade.php` | Server error — "Something went wrong, please try again" |
| `404.blade.php` | Page not found (if module-specific needed) |
| `validation.blade.php` | Validation failure messages |
| `generic.blade.php` | Unexpected error fallback |

### 5.1. Design Language

All error views follow the site's design system:

- **Primary color:** `#0f172a` (dark mode) / `#f8fafc` (light mode)
- **Accent color:** `#3b82f6` (brand blue)
- **Text (dark):** `#f8fafc`, **Text (light):** `#0f172a`
- **Responsive:** Max width 600px, centered
- **Theme-aware:** Detects `data-theme` attribute for dark/light mode
- **CTA:** "Return to Shop" / "Try Again" button linking to homepage or previous page

### 5.2. Example: `Modules/PartnerHub/errors/500.blade.php`

```blade
{{-- Partner Hub error page --}}
<div class="pc-panel pc-panel--error" style="background: var(--surface-100); color: var(--text-900);">
    <div class="pc-panel__header">
        <h3 class="pc-panel__title">Something went wrong</h3>
    </div>
    <div class="pc-panel__body">
        <p class="pc-panel__text">
            We're experiencing technical difficulties. Please try again or contact support.
        </p>
        <a href="{{ route('partner.dashboard') }}" 
           class="btn btn-primary">
            Return to Dashboard
        </a>
    </div>
</div>
```

---

## 6. Role Detection & Response Differentiation

Each Handler determines the response based on user role:

| User Type | Experience |
|-----------|------------|
| **Guest** (no auth) | Friendly "try again" message, error logged to `customer-errors.log` |
| **Partner** (vendor) | Friendly message relevant to partner context, error logged to module channel (e.g., `identity-errors.log`) |
| **Admin** | Detailed error information still written to `laravel.log` (unchanged behavior) |

**Example detection logic:**

```php
$user = auth()->user();
$isAdmin = $user && $user->hasRole('admin');
$isPartner = $user && $user->hasRole('partner');

if ($isAdmin) {
    // Admins: full details in log, show technical page (or redirect to debug)
    // Laravel default behavior preserved via parent::report()
}

if ($isPartner || is_guest()) {
    // Partners/guests: hide all technical details
    // Serve friendly view in render()
}
```

---

## 7. Success Criteria

- [ ] **Customers** accessing `/shop` encountering a DB error see: "Something went wrong, please try again" — **no SQL/stack trace visible**
- [ ] **Partners** accessing partner portal encountering an error see: friendly message appropriate to their context
- [ ] **Admins** encountering an error still see detailed logs in `storage/logs/laravel.log`
- [ ] **All module errors** appear in their respective log files:
  - `identity-errors.log` — Identity/partner portal errors
  - `pipeline-errors.log` — Checkout/order errors
  - `catalog-errors.log` — Product/catalog errors
  - `partner-errors.log` — PartnerHub errors
- [ ] **Existing 404 page** continues to work alongside new system
- [ ] **All 203 existing tests** still pass
- [ ] **Middleware** properly registered and routes to correct module handler
- [ ] **Log files rotate** correctly (default Laravel log rotation applies)

---

## 8. Migration / Upgrade Path

### 8.1. Adding New Modules

When a new module is added to the monolith:

1. Create `Modules/{ModuleName}/Exceptions/Handler.php` extending `Illuminate\Foundation\Exceptions\Handler`
2. Add module's error views to `resources/views/modules/{ModuleName}/errors/`
3. Add module's log channel to `config/logging.php` (optional — can share existing channels)
4. Add routing rule to `ModuleExceptionHandler::findModuleHandler()` if needed

### 8.2. Rolling Back

To revert to the previous single-Handler approach:

1. Remove the `ModuleExceptionHandler` middleware from `app/Http/Kernel.php`
2. Delete the four new log channels from `config/logging.php`
3. Delete the module-specific `Exceptions/Handler.php` files
4. Delete module error views
5. Remove middleware references

---

## 9. Current State Assessment

### 9.1. What Already Exists

- ✅ `resources/views/errors/404.blade.php` — existing 404 page (kept, not replaced)
- ✅ `config/logging.php` — existing logging configuration (extended with 4 new channels)
- ✅ `app/Exceptions/Handler.php` — base Laravel Handler (unchanged, delegation via middleware)
- ✅ 203 tests passing (854 assertions)

### 9.2. What Needs to Be Created

- [ ] `Modules/IdentityAccess/Exceptions/Handler.php`
- [ ] `Modules/MarketplacePipeline/Exceptions/Handler.php`
- [ ] `Modules/CatalogDelivery/Exceptions/Handler.php`
- [ ] `Modules/PartnerHub/Exceptions/Handler.php`
- [ ] `app/Http/Middleware/ModuleExceptionHandler.php`
- [ ] `config/logging.php` — 4 new channels added
- [ ] `resources/views/modules/{ModuleName}/errors/500.blade.php` × 4 modules
- [ ] `resources/views/modules/{ModuleName}/errors/404.blade.php` × 4 modules (optional)
- [ ] `resources/views/modules/{ModuleName}/errors/validation.blade.php` × 4 modules
- [ ] `resources/views/modules/{ModuleName}/errors/generic.blade.php` × 4 modules
- [ ] Kernel middleware registration in `app/Http/Kernel.php`

### 9.3. Database Impact

- **Zero** — this is purely a logging/error-view change
- No migrations needed
- No data changes
- No seed changes

### 9.4. Performance Impact

- Negligible — log file writes are I/O-bound but infrequent (error events)
- Middleware adds one additional path check per request (microseconds)
- No changes to normal request flow when no errors occur

---

## 10. Questions & Future Considerations

| Question | Status |
|----------|--------|
| Should errors also be sent to Sentry/Rollbar? | Deferred — out of scope for this design |
| Should error views include a "report to us" button? | Deferred — requires backend changes |
| Should different modules share log channels? | No — each module owns its channel for clear separation |
| Should we implement error rate alerting? | Future enhancement, not in this design |

---

## 11. Approval

**Designed by:** Development Team  
**Date:** 2026-08-31  
**Approved by:** [User]  
**Next step:** Implementation plan via writing-plans skill  

--- 

*This is a living document. Update as the implementation progresses or as new requirements emerge.*