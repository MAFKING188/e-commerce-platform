# Email-OTP 2FA for all risk tiers — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the TOTP authenticator-app 2FA with mandatory email-code verification (admins + partners at every login, buyers at signup + sensitive actions), removing all TOTP machinery.

**Architecture:** The challenge moves before login — `Auth::login` only runs after a valid 6-digit email code, so privileged sessions are impossible without verification and the old enrollment-gate middleware becomes dead code and is deleted. Buyers get a signup verification step, an optional login-code toggle, and a step-up code at checkout / password change / email change / 2FA settings changes, backed by a small `StepUpService` over the existing `OtpService` (6 digits, 10-min TTL, hashed at rest, single-use, attempt-limited).

**Tech Stack:** Laravel 11 module monolith (nwidart/laravel-modules), Blade, MySQL, database cache + queue. Spec: `docs/superpowers/specs/2026-08-19-email-otp-2fa-design.md`.

## Global Constraints

- Route names are immutable once created: `2fa.challenge`, `2fa.verify`, `2fa.resend`, `profile.settings.twofa.enable-email`, `profile.settings.twofa.confirm`, `profile.settings.twofa.disable`, `orders.store`, `profile.update`, `profile.password`, `signup`, `createaccount` keep their exact names. New routes get new names.
- No `style=""` inline attributes in blades (email templates exempt). Module CSS classes unique app-wide.
- No `git add .` — explicit staging. Conventional commits. TDD: failing test first, then minimal implementation, then commit per task.
- Modules PSR-4: `Modules\IdentityAccess\` → `Modules/IdentityAccess/app/`, `Modules\MarketplacePipeline\` → `Modules/MarketplacePipeline/app/`.
- Rate limiters live in `Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php` (already defined: `auth`, `checkout`, `2fa`, `2fa-resend`, `2fa-enroll`). A new `2fa-verify` limiter is added there in Task 6.
- Generic, enumeration-safe messages for all OTP sends.
- Tests run with `php artisan test`; MAIL_MAILER=array + QUEUE_CONNECTION=sync are overridden in `phpunit.xml`.

---

### Task 1: Migration — email_verified_at + drop two_factor_secret

**Files:**
- Create: `Modules/IdentityAccess/database/migrations/2026_08_19_140001_email_otp_2fa.php`
- Modify: `Modules/IdentityAccess/app/Enums/TwoFactorType.php`
- Modify: `Modules/IdentityAccess/app/Models/User.php` (casts, `isPartner()`)

**Interfaces:**
- Produces: `users.email_verified_at` (nullable timestamp), `users.two_factor_secret` removed, `TwoFactorType::Email` (only case), `User::isPartner(): bool`.

- [ ] **Step 1: Write the failing test**

Create `Modules/IdentityAccess/tests/Feature/TwoFactorSchemaTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Enums\TwoFactorType;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class TwoFactorSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_have_email_verified_at_column(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->email_verified_at);
    }

    public function test_two_factor_type_enum_has_only_email(): void
    {
        $cases = array_map(fn ($c) => $c->value, TwoFactorType::cases());
        $this->assertEquals(['email'], $cases);
    }

    public function test_is_partner_detects_partner_role(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $user = User::factory()->create(['role' => 'user']);
        $this->assertTrue($partner->isPartner());
        $this->assertFalse($user->isPartner());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorSchemaTest.php`
Expected: FAIL — column does not exist / `TwoFactorType::Totp` still present / `isPartner()` undefined.

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->dropColumn('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable();
            $table->dropColumn('email_verified_at');
        });
    }
};
```

- [ ] **Step 4: Trim the enum and add isPartner**

`Modules/IdentityAccess/app/Enums/TwoFactorType.php` becomes:

```php
<?php

namespace Modules\IdentityAccess\Enums;

enum TwoFactorType: string
{
    case Email = 'email';
}
```

`Modules/IdentityAccess/app/Models/User.php` — after `isAdmin()` (line ~166):

```php
    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorSchemaTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**

```bash
git add Modules/IdentityAccess/database/migrations/2026_08_19_140001_email_otp_2fa.php Modules/IdentityAccess/app/Enums/TwoFactorType.php Modules/IdentityAccess/app/Models/User.php Modules/IdentityAccess/tests/Feature/TwoFactorSchemaTest.php
git commit -m "feat(2fa): email-only schema — email_verified_at added, two_factor_secret dropped, isPartner() helper"
```

---

### Task 2: Challenge-before-login — mandatory email code for admins + partners

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/AuthController.php` (login)
- Modify: `Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php` (remove TOTP paths, mandatory gate)
- Modify: `Modules/IdentityAccess/routes/web.php` (remove enable-totp + qr routes; strip `2fa.enrolled` from admin groups)
- Modify: `Modules/CatalogDelivery/routes/web.php`, `Modules/MarketplacePipeline/routes/web.php`, `Modules/PartnerHub/routes/web.php` (strip `2fa.enrolled`)
- Modify: `bootstrap/app.php` (remove `2fa.enrolled` alias)
- Delete: `Modules/IdentityAccess/app/Http/Middleware/Ensure2faEnrolled.php`
- Modify: `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` (rewrite challenge section)
- Modify: `Modules/IdentityAccess/resources/views/auth/challenge.blade.php` (email-only)

**Interfaces:**
- Consumes: `OtpService::send(User)`, `User::isPartner()`, `User::twoFactorEnabled()`.
- Produces: login sets `2fa.pending` + `2fa.attempts=0`; `TwoFactorController::verify` calls `Auth::login` only on valid code; no `2fa.required` session key anywhere.

- [ ] **Step 1: Rewrite the failing tests**

Replace `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` entirely:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function loginViaChallenge(string $email, string $password): void
    {
        $this->post('/accessaccount', ['email' => $email, 'password' => $password])
            ->assertRedirect('/2fa/challenge');
        $this->assertNotNull(session('2fa.pending'));
    }

    public function test_admin_login_requires_email_code_without_enrollment(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->loginViaChallenge($admin->email, 'password');
        $this->get('/2fa/challenge')->assertOk();
    }

    public function test_partner_login_requires_email_code_without_enrollment(): void
    {
        $partner = User::factory()->create(['status' => 'active', 'role' => 'partner']);
        $this->loginViaChallenge($partner->email, 'password');
        $this->get('/2fa/challenge')->assertOk();
    }

    public function test_admin_cannot_reach_admin_pages_before_challenge(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_admin_challenge_with_valid_code_logs_in(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $code = OtpService::issue($admin);
        $this->post('/2fa/challenge/verify', ['code' => $code])->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_challenge_with_invalid_code_fails_and_keeps_pending(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $this->post('/2fa/challenge/verify', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_five_invalid_codes_invalidate_session(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        foreach (range(1, 5) as $i) {
            $this->post('/2fa/challenge/verify', ['code' => '000000']);
        }
        $this->assertGuest();
        $this->assertNull(session('2fa.pending'));
    }

    public function test_buyer_without_2fa_logs_in_directly(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_resend_issues_new_code(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $this->post('/2fa/challenge/resend')->assertSessionHasNoErrors();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: FAIL — partner login bypasses challenge; admin gate redirects to settings, etc.

- [ ] **Step 3: Refactor AuthController::login**

`Modules/IdentityAccess/app/Http/Controllers/AuthController.php`, replace lines 99-118:

```php
        if ($user->isAdmin() || $user->isPartner() || $user->twoFactorEnabled()) {
            session([
                '2fa.pending' => $user->id,
                '2fa.attempts' => 0,
                '2fa.pending_method' => 'email',
            ]);
            \Modules\IdentityAccess\Services\OtpService::send($user);
            return redirect()->route('2fa.challenge');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
```

- [ ] **Step 4: Trim TwoFactorController to email-only**

`Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php`:

- Remove imports `PragmaRX\Google2FALaravel\Facade`, `SimpleSoftwareIO\QrCode\Facades\QrCode` (lines 13-14).
- Remove `OTP_TTL` const (line 18) and `verifyTotp()` (lines 99-110).
- `challenge()` guard (line 29) becomes:

```php
        if (! $user || (! $user->twoFactorEnabled() && ! $user->isAdmin() && ! $user->isPartner())) {
```

- `verify()` (line 46): same guard replacement; delete the `$method`/totp branch — always `OtpService::check($user, trim($data['code']))`. Delete lines 72-75 (the `isAdmin` / `2fa.required` block).
- Delete `enableTotp()` (lines 114-130) and `qr()` (lines 132-150).
- `enableEmail()` (line 154): unchanged behavior (sends code, sets `twofa.pending_type` = `'email'`).
- `confirm()` (lines 174-207): delete the `$pending === 'totp'` branch and the `'2fa.required'` from the forget list (line 203). Confirmation is email-only:

```php
        $valid = OtpService::check($user, trim($data['code']));
```

- `disable()` (lines 211-231): remove the `if ($user->isAdmin()) session(['2fa.required' => true]);` block (lines 225-227) and the `'two_factor_secret' => null` line (221).

- [ ] **Step 5: Update routes + delete middleware**

`Modules/IdentityAccess/routes/web.php`: delete lines 40 and 42 (enable-totp + qr routes); line 50 drops `2fa.enrolled`:

```php
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
```

`Modules/CatalogDelivery/routes/web.php`, `Modules/MarketplacePipeline/routes/web.php`, `Modules/PartnerHub/routes/web.php`: same — remove `'2fa.enrolled'` from the admin group middleware arrays.

`bootstrap/app.php` line 22: delete the `2fa.enrolled` alias.

Delete file `Modules/IdentityAccess/app/Http/Middleware/Ensure2faEnrolled.php`.

- [ ] **Step 6: Update challenge blade to email-only**

`Modules/IdentityAccess/resources/views/auth/challenge.blade.php` — replace lines 9-17:

```blade
        <p class="twofa-hint">A 6-digit code was sent to <strong>{{ $user->email }}</strong>. It expires in 10 minutes.</p>
        <form method="POST" action="{{ route('2fa.resend') }}" class="twofa-form">
            @csrf
            <button type="submit" class="btn btn-ghost">Resend Code</button>
        </form>
```

- [ ] **Step 7: Run the challenge tests**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: PASS (8 tests).

- [ ] **Step 8: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/AuthController.php Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php Modules/IdentityAccess/routes/web.php Modules/CatalogDelivery/routes/web.php Modules/MarketplacePipeline/routes/web.php Modules/PartnerHub/routes/web.php bootstrap/app.php Modules/IdentityAccess/resources/views/auth/challenge.blade.php Modules/IdentityAccess/tests/Feature/TwoFactorTest.php
git rm Modules/IdentityAccess/app/Http/Middleware/Ensure2faEnrolled.php
git commit -m "feat(2fa): challenge-before-login — mandatory email code for admins and partners, TOTP removed"
```

---

### Task 3: Buyer opt-in toggle — email codes in settings

**Files:**
- Modify: `Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php`
- Test: `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` (add 3 tests)

**Interfaces:**
- Consumes: `TwoFactorController::enableEmail` / `confirm` / `disable`, session `twofa.pending_type`.

- [ ] **Step 1: Add failing tests**

Append to `TwoFactorTest.php`:

```php
    public function test_buyer_can_enable_email_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $this->post('/profile/settings/twofa/enable-email', ['password' => 'password'])
            ->assertSessionHasNoErrors();
        $code = OtpService::issue($user);
        $this->post('/profile/settings/twofa/confirm', ['code' => $code])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue($user->twoFactorEnabled());
    }

    public function test_buyer_opt_in_then_login_requires_challenge(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $user->forceFill(['two_factor_type' => 'email', 'two_factor_confirmed_at' => now()])->save();
        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/2fa/challenge');
    }

    public function test_buyer_can_disable_email_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $user->forceFill(['two_factor_type' => 'email', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($user);
        $this->post('/profile/settings/twofa/disable', ['password' => 'password'])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertFalse($user->twoFactorEnabled());
    }
```

(Note: `disable` gains a code requirement in Task 5 — this test is written against the current signature and updated there.)

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: the three new tests FAIL (two_factor_type cast/UI differences or missing behavior).

- [ ] **Step 3: Rewrite the twofa-card partial**

`Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php` — replace the whole `@if ($user->twoFactorEnabled()) ... @else ... @endif` block with:

```blade
    @if ($user->twoFactorEnabled())
        <p class="twofa-status">Enabled via <span class="twofa-badge">Email Codes</span>
            since {{ $user->two_factor_confirmed_at->format('M j, Y') }}.</p>
        <form action="{{ route('profile.settings.twofa.disable') }}" method="POST" class="twofa-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="password" class="form-input" required>
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-ghost btn-danger">Disable Two-Factor Authentication</button>
        </form>
    @else
        <p class="twofa-status">Your account is protected by a password only. Add a second verification step to keep it secure.</p>

        @if (session('twofa.pending_type') === 'email')
            <div class="twofa-setup">
                <p class="twofa-hint">A 6-digit code was sent to <strong>{{ $user->email }}</strong>. It expires in 10 minutes.</p>
                <form action="{{ route('profile.settings.twofa.confirm') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Verification Code</label>
                        <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                        @error('code') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Confirm &amp; Enable</button>
                </form>
            </div>
        @else
            <form action="{{ route('profile.settings.twofa.enable-email') }}" method="POST" class="twofa-option">
                @csrf
                <p class="twofa-option-title">Email Codes</p>
                <p class="twofa-option-desc">Receive a 6-digit code by email at each sign-in.</p>
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary">Enable Email Codes</button>
            </form>
        @endif

        @if ($errors->has('twofa'))
            <p class="form-error">{{ $errors->first('twofa') }}</p>
        @endif
    @endif
```

- [ ] **Step 4: Run tests**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: PASS (11 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php Modules/IdentityAccess/tests/Feature/TwoFactorTest.php
git commit -m "feat(2fa): settings toggle — single email-codes flow, TOTP QR UI removed"
```

---

### Task 4: Checkout step-up verification

**Files:**
- Create: `Modules/IdentityAccess/app/Services/StepUpService.php`
- Modify: `Modules/MarketplacePipeline/app/Http/Controllers/OrderController.php`
- Modify: `Modules/MarketplacePipeline/resources/views/cart/index.blade.php`
- Create: `Modules/MarketplacePipeline/tests/Feature/CheckoutStepUpTest.php`

**Interfaces:**
- Consumes: `OtpService::send/check`, session.
- Produces: `StepUpService::begin(User $user, SessionStore $session)`, `StepUpService::isVerified(SessionStore $session): bool`, `StepUpService::complete(SessionStore $session)`, `StepUpService::invalidate(SessionStore $session)` — `SessionStore` is `Illuminate\Session\Store`.

- [ ] **Step 1: Write the failing test**

Create `Modules/MarketplacePipeline/tests/Feature/CheckoutStepUpTest.php`:

```php
<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;
use Tests\TestCase;

class CheckoutStepUpTest extends TestCase
{
    use RefreshDatabase;

    private function setUpCart(User $user): Product
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->actingAs($user);
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
        return $product;
    }

    private function payload(): array
    {
        return [
            'recipient_name' => 'QA Buyer',
            'recipient_phone' => '+212 600 000 000',
            'shipping_line1' => '1 Test Street',
            'shipping_city' => 'Casablanca',
            'shipping_country' => 'Morocco',
        ];
    }

    public function test_checkout_without_code_asks_for_code_and_does_not_create_order(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $this->post('/orders/store', $this->payload())
            ->assertSessionHasErrors('code');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_with_valid_code_creates_order_and_marks_verified(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $code = OtpService::issue($user);
        $this->post('/orders/store', $this->payload() + ['code' => $code])
            ->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertNotNull(session('stepup.verified'));
    }

    public function test_verified_marker_bypasses_repeat_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $code = OtpService::issue($user);
        $this->post('/orders/store', $this->payload() + ['code' => $code]);
        $product = Product::latest('id')->first();
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/orders/store', $this->payload())
            ->assertRedirect();
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_invalid_code_sends_new_code_and_does_not_create_order(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $this->post('/orders/store', $this->payload() + ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertDatabaseCount('orders', 0);
    }
}
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/MarketplacePipeline/tests/Feature/CheckoutStepUpTest.php`
Expected: FAIL — no code requirement at checkout yet.

- [ ] **Step 3: Create StepUpService**

`Modules/IdentityAccess/app/Services/StepUpService.php`:

```php
<?php

namespace Modules\IdentityAccess\Services;

use Illuminate\Session\Store as SessionStore;
use Modules\IdentityAccess\Models\User;

class StepUpService
{
    private const VERIFIED_TTL = 900;

    public static function begin(User $user, SessionStore $session): void
    {
        OtpService::send($user);
        $session->put('stepup.pending', true);
    }

    public static function isVerified(SessionStore $session): bool
    {
        $verifiedAt = $session->get('stepup.verified');
        return is_int($verifiedAt) && ($verifiedAt + self::VERIFIED_TTL) > time();
    }

    public static function complete(SessionStore $session): void
    {
        $session->put('stepup.verified', time());
        $session->forget('stepup.pending');
    }

    public static function invalidate(SessionStore $session): void
    {
        $session->forget(['stepup.pending', 'stepup.verified']);
    }
}
```

- [ ] **Step 4: Gate OrderController::store**

`Modules/MarketplacePipeline/app/Http/Controllers/OrderController.php` — after the `$delivery = $request->validate([...]);` block, before `$checkout->checkout(...)`:

```php
        $user = auth()->user();
        $session = $request->session();

        if (! \Modules\IdentityAccess\Services\StepUpService::isVerified($session)) {
            $code = trim((string) $request->input('code'));

            if ($code === '' || ! \Modules\IdentityAccess\Services\OtpService::check($user, $code)) {
                \Modules\IdentityAccess\Services\StepUpService::begin($user, $session);
                return back()->withErrors(['code' => 'Enter the verification code sent to your email. A new code has been sent.']);
            }

            \Modules\IdentityAccess\Services\StepUpService::complete($session);
        }

        try {
            $order = $checkout->checkout($user, $delivery);
```

(replace `auth()->user()` at the `Mail::to(auth()->user())` line with `$user` if convenient — not required.)

- [ ] **Step 5: Add the code field to the checkout form**

`Modules/MarketplacePipeline/resources/views/cart/index.blade.php` — insert before the closing `</form>` (after the delivery section, line 126):

```blade
        @if (session('stepup.pending') || $errors->has('code'))
            <div class="delivery-section">
                <div class="delivery-head">
                    <h2>Verify Your Email</h2>
                    <p>A 6-digit code was sent to <strong>{{ auth()->user()->email }}</strong>. Enter it to confirm your order. It expires in 10 minutes.</p>
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="code">Verification Code</label>
                    <input type="text" name="code" id="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                    @error('code') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif
```

- [ ] **Step 6: Run tests**

Run: `php artisan test Modules/MarketplacePipeline/tests/Feature/CheckoutStepUpTest.php`
Expected: PASS (4 tests).

- [ ] **Step 7: Commit**

```bash
git add Modules/IdentityAccess/app/Services/StepUpService.php Modules/MarketplacePipeline/app/Http/Controllers/OrderController.php Modules/MarketplacePipeline/resources/views/cart/index.blade.php Modules/MarketplacePipeline/tests/Feature/CheckoutStepUpTest.php
git commit -m "feat(checkout): step-up email verification before order placement"
```

---

### Task 5: Step-up for password change, email change, and 2FA disable

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/UserController.php`
- Modify: `Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php` (disable)
- Modify: `Modules/IdentityAccess/resources/views/users/security.blade.php` (password + code field)
- Modify: `Modules/IdentityAccess/resources/views/users/settings.blade.php` (email + code field)
- Modify: `Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php` (disable form gains code field)
- Modify: `Modules/IdentityAccess/tests/Feature/ProfileTest.php` (code requirement tests)
- Modify: `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` (disable test gains code)

**Interfaces:**
- Consumes: `OtpService::send/check`, `StepUpService`.

- [ ] **Step 1: Write the failing tests**

Append to `Modules/IdentityAccess/tests/Feature/ProfileTest.php`:

```php
    public function test_password_change_requires_verification_code(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('code');
        $user->refresh();
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->password));
    }

    public function test_password_change_with_valid_code_applies(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $code = \Modules\IdentityAccess\Services\OtpService::issue($user);
        $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'code' => $code,
        ])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->password));
    }

    public function test_email_change_requires_verification_code(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $this->put('/profile/update', [
            'name' => 'QA Buyer',
            'email' => 'newmail@test.com',
            'phone' => '+212 600 000 000',
        ])->assertSessionHasErrors('code');
        $user->refresh();
        $this->assertNotEquals('newmail@test.com', $user->email);
    }

    public function test_email_change_with_valid_code_applies(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $code = \Modules\IdentityAccess\Services\OtpService::issue($user);
        $this->put('/profile/update', [
            'name' => 'QA Buyer',
            'email' => 'newmail@test.com',
            'phone' => '+212 600 000 000',
            'code' => $code,
        ])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertEquals('newmail@test.com', $user->email);
    }

    public function test_address_only_save_still_works_without_code(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $this->put('/profile/update', [
            'line1' => '88 Boulevard Mohammed V',
            'city' => 'Casablanca',
            'country' => 'Morocco',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('addresses', ['city' => 'Casablanca']);
    }
```

Update in `TwoFactorTest.php` the `test_buyer_can_disable_email_codes` test:

```php
    public function test_buyer_can_disable_email_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $user->forceFill(['two_factor_type' => 'email', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($user);
        $code = OtpService::issue($user);
        $this->post('/profile/settings/twofa/disable', ['password' => 'password', 'code' => $code])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertFalse($user->twoFactorEnabled());
    }
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/ProfileTest.php Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: new tests FAIL — code not required yet.

- [ ] **Step 3: Implement — password change**

`Modules/IdentityAccess/app/Http/Controllers/UserController.php`, in `updatePassword()`, replace the validation block with:

```php
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (! \Illuminate\Support\Facades\Hash::check($value, Auth::user()->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => 'required|string|min:8|confirmed',
            'code' => 'required|string|max:10',
        ]);

        if (! \Modules\IdentityAccess\Services\OtpService::check(Auth::user(), trim($request->code))) {
            \Modules\IdentityAccess\Services\OtpService::send(Auth::user());
            return back()->withErrors(['code' => 'The code is invalid or has expired. A new code was sent to your email.']);
        }
```

- [ ] **Step 4: Implement — email change**

`Modules/IdentityAccess/app/Http/Controllers/UserController.php`, in `updateProfile()`, after the existing `$request->validate([...])` and before `$user->update(...)`:

```php
        if ($request->filled('email') && strtolower($request->email) !== strtolower($user->email)) {
            $request->validate(['code' => 'required|string|max:10']);
            if (! \Modules\IdentityAccess\Services\OtpService::check($user, trim($request->code))) {
                \Modules\IdentityAccess\Services\OtpService::send($user);
                return back()->withErrors(['code' => 'The code is invalid or has expired. A new code was sent to your email.']);
            }
        }
```

- [ ] **Step 5: Implement — 2FA disable**

`Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php`, `disable()`:

```php
        $data = $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|max:10',
        ]);

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Current password is incorrect.']);
        }

        if (! OtpService::check($user, trim($data['code']))) {
            OtpService::send($user);
            return back()->withErrors(['code' => 'The code is invalid or has expired. A new code was sent to your email.']);
        }
```

- [ ] **Step 6: Update the forms**

- `Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php` — disable form gains:

```blade
            <div class="form-group">
                <label class="form-label">Verification Code</label>
                <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required>
                @error('code') <p class="form-error">{{ $message }}</p> @enderror
            </div>
```

- `Modules/IdentityAccess/resources/views/users/security.blade.php` — password form gains a `code` input (same markup, label "Verification Code", hint "A code was sent to your email.").
- `Modules/IdentityAccess/resources/views/users/settings.blade.php` — email field form gains a `code` input (same markup). Inspect both files before editing and place the input inside the same form as the submit button; verify no inline styles.

- [ ] **Step 7: Run tests**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/ProfileTest.php Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/UserController.php Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php Modules/IdentityAccess/resources/views/users/security.blade.php Modules/IdentityAccess/resources/views/users/settings.blade.php Modules/IdentityAccess/tests/Feature/ProfileTest.php Modules/IdentityAccess/tests/Feature/TwoFactorTest.php
git commit -m "feat(2fa): step-up codes for password change, email change, and 2FA disable"
```

---

### Task 6: Signup email verification

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/AuthController.php` (register + login + 3 new methods)
- Modify: `Modules/IdentityAccess/routes/web.php` (3 new routes)
- Modify: `Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php` (`2fa-verify` limiter)
- Create: `Modules/IdentityAccess/resources/views/auth/verify-email.blade.php`
- Modify: `Modules/IdentityAccess/tests/Feature/RegistrationTest.php`

**Interfaces:**
- Consumes: `OtpService::send/check`, session key `email.verify.pending`.
- Produces: routes `verify-email`, `verify-email.post`, `verify-email.resend`; `User::markEmailAsVerified()` (inline forceFill, no new model method needed).

- [ ] **Step 1: Add failing tests**

Append to `Modules/IdentityAccess/tests/Feature/RegistrationTest.php`:

```php
    public function test_signup_redirects_to_verify_email_with_unverified_account(): void
    {
        $this->post('/createaccount', [
            'name' => 'New Buyer',
            'email' => 'buyerx@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
            'phone' => '+212 600 000 000',
            'country' => 'MA',
        ])->assertRedirect(route('verify-email'));

        $user = \Modules\IdentityAccess\Models\User::where('email', 'buyerx@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }

    public function test_verify_email_with_valid_code_marks_verified(): void
    {
        $this->post('/createaccount', [
            'name' => 'New Buyer',
            'email' => 'buyerx@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
            'phone' => '+212 600 000 000',
            'country' => 'MA',
        ]);

        $user = \Modules\IdentityAccess\Models\User::where('email', 'buyerx@test.com')->first();
        $code = \Modules\IdentityAccess\Services\OtpService::issue($user);
        $this->post('/verify-email', ['code' => $code])->assertRedirect('/');
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_unverified_login_redirects_to_verify_email(): void
    {
        $user = \Modules\IdentityAccess\Models\User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('verify-email'));
    }

    public function test_verify_email_resend_route_exists(): void
    {
        $this->post('/verify-email/resend')->assertRedirect('/login');
    }
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/RegistrationTest.php`
Expected: FAIL — no verify-email routes; signup still auto-logs in and redirects home.

- [ ] **Step 3: Update register()**

`Modules/IdentityAccess/app/Http/Controllers/AuthController.php`, replace lines 59-67:

```php
        // 📧 Trigger Welcome Email
        Mail::to($user)->queue(new WelcomeMember($user));

        if ($status === 'active') {
            Auth::login($user);
            $request->session()->regenerate();
            \Modules\IdentityAccess\Services\OtpService::send($user);
            session(['email.verify.pending' => $user->id]);
            return redirect()->route('verify-email')->with('status', 'Welcome to the Collection! Verify your email to finish signing up.');
        }

        return redirect('/login')->with('status', 'Account request received. Please wait for administrative confirmation.');
```

- [ ] **Step 4: Update login()**

`Modules/IdentityAccess/app/Http/Controllers/AuthController.php`, in `login()`, after the `Auth::validate` check (after line 97) and before the 2FA block:

```php
        if ($user->email_verified_at === null) {
            \Modules\IdentityAccess\Services\OtpService::send($user);
            session(['email.verify.pending' => $user->id]);
            return redirect()->route('verify-email')->with('status', 'A verification code was sent to your email.');
        }
```

- [ ] **Step 5: Add the three verify methods to AuthController**

```php
    /* EMAIL VERIFICATION */
    public function verifyEmailPage()
    {
        $userId = session('email.verify.pending');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->route('login');
        }

        return view('identityaccess::auth.verify-email', ['user' => $user]);
    }

    public function verifyEmail(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:10']);
        $userId = session('email.verify.pending');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->route('login');
        }

        if (! \Modules\IdentityAccess\Services\OtpService::check($user, trim($data['code']))) {
            \Modules\IdentityAccess\Services\OtpService::send($user);
            return back()->withErrors(['code' => 'The code is invalid or has expired. A new code was sent to your email.']);
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        session()->forget('email.verify.pending');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('status', 'Email verified. Welcome to the Collection!');
    }

    public function resendVerifyEmail()
    {
        $userId = session('email.verify.pending');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->route('login');
        }

        \Modules\IdentityAccess\Services\OtpService::send($user);

        return back()->with('status', 'A new verification code was sent to your email.');
    }
```

- [ ] **Step 6: Add routes + limiter**

`Modules/IdentityAccess/routes/web.php` — after the `signup` route (line 12):

```php
    Route::get('/verify-email', [AuthController::class, 'verifyEmailPage'])->name('verify-email');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:2fa-verify')->name('verify-email.post');
    Route::post('/verify-email/resend', [AuthController::class, 'resendVerifyEmail'])->middleware('throttle:2fa-resend')->name('verify-email.resend');
```

`Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php` — add after line 27:

```php
        RateLimiter::for('2fa-verify', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
```

- [ ] **Step 7: Create the verify-email blade**

`Modules/IdentityAccess/resources/views/auth/verify-email.blade.php`:

```blade
@section('title', 'Verify Your Email | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card auth-card--narrow">
        <h1 class="auth-title">Verify Your Email</h1>
        <p class="auth-subtitle">Enter the code we sent to <strong>{{ $user->email }}</strong> to activate your account.</p>

        <form method="POST" action="{{ route('verify-email.post') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Verification Code</label>
                <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                @error('code') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            @if (session('status'))
                <p class="form-success">{{ session('status') }}</p>
            @endif

            <button type="submit" class="auth-button">Verify Email</button>
        </form>

        <form method="POST" action="{{ route('verify-email.resend') }}" class="twofa-form">
            @csrf
            <button type="submit" class="btn btn-ghost">Resend Code</button>
        </form>
    </div>
</div>
</x-app-layout>
```

- [ ] **Step 8: Run tests**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/RegistrationTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/AuthController.php Modules/IdentityAccess/routes/web.php Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php Modules/IdentityAccess/resources/views/auth/verify-email.blade.php Modules/IdentityAccess/tests/Feature/RegistrationTest.php
git commit -m "feat(auth): mandatory email verification at signup with resend + throttle"
```

---

### Task 7: Full suite green + docs

**Files:**
- Verify: full `php artisan test` suite.
- Modify: `docs/superpowers/specs/2026-08-19-email-otp-2fa-design.md` (no change needed unless code drifted).
- Modify: `PROJECT_REPORT.txt` (new section 14).

- [ ] **Step 1: Run the full suite**

Run: `php artisan test`
Expected: ALL green (previous baseline 86 tests / 335 assertions + new tests).

- [ ] **Step 2: Fix any stragglers**

Any test still referencing TOTP, `two_factor_secret`, `2fa.required`, `Ensure2faEnrolled`, or the `enable-totp`/`qr` routes must be updated to the email-only reality. Run the suite until green.

- [ ] **Step 3: Update PROJECT_REPORT.txt**

Append section 14: email-OTP 2FA (spec + plan paths, commits, design summary: challenge-before-login, mandatory admin/partner, buyer opt-in + step-up at checkout/password/email/2FA settings, signup verification, TOTP removal, throttles).

- [ ] **Step 4: Commit**

```bash
git add PROJECT_REPORT.txt
git commit -m "docs(report): section 14 — email-OTP 2FA for all risk tiers"
```

- [ ] **Step 5: Deploy to production**

```bash
git push origin main
ssh root@104.248.163.215 "cd /var/www/smartshop && git pull && php artisan migrate --force && php artisan route:clear -q && php artisan config:clear -q && php artisan queue:restart"
```

- [ ] **Step 6: Verify live**

- Signup on prod → verify-email page appears → code arrives by email.
- Admin login on prod → challenge page → code by email → console accessible.
- Buyer checkout on prod → code step → order placed.
- Settings page shows only "Enable Email Codes".