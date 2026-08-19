# 2FA + Google Sign-In Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Production-grade two-factor authentication (TOTP + email OTP, user's choice, admins mandatory) and Google OAuth sign-up/login on the existing custom auth stack.

**Architecture:** All new code in `Modules/IdentityAccess`. Two new controllers (`TwoFactorController`, `GoogleAuthController`), two middleware aliases (`2fa.pending`, `2fa.enrolled`), one enum, one migration, one mailable. Login stays custom (`AuthController`/`/accessaccount`) — the password step now defers full auth to a challenge step.

**Tech Stack:** Laravel 13, PHP 8.3, laravel/socialite v5, pragmarx/google2fa-laravel, simplesoftwareio/simple-qrcode, database queue (worker on server), Mail SMTP (Gmail).

## Global Constraints

- Route names immutable; only ADD new routes (`2fa.challenge`, `2fa.verify`, `2fa.resend`, `profile.settings.twofa.*`, `auth.google.redirect`, `auth.google.callback`).
- Zero inline `style=` attributes in blades (email templates exempt).
- Module CSS classes must be unique app-wide (module bundles load globally) — prefix new classes `auth-` / `twofa-`.
- Tests live in `Modules/*/tests`; existing 48 tests use `actingAs` and must stay green.
- No `git add .` — stage explicit files. Commit message style: `feat:` / `fix:` / `docs:` + short description.
- App live at smartshop-luwi.tech; ship only with suite green; deploy via push → server pull → composer install → migrate → build.

---

### Task 1: Foundation — deps, migration, enum, model helpers

**Files:**
- Modify: `composer.json` (via composer require)
- Create: `Modules/IdentityAccess/database/migrations/2026_08_19_000004_add_2fa_and_google_to_users_table.php`
- Create: `Modules/IdentityAccess/app/Enums/TwoFactorType.php`
- Modify: `Modules/IdentityAccess/app/Models/User.php`
- Test: `Modules/IdentityAccess/tests/Feature/UserDetailsTest.php` (append tests)

**Interfaces:**
- Consumes: nothing new.
- Produces: `TwoFactorType` enum (`Modules\IdentityAccess\Enums\TwoFactorType`, string-backed `totp`/`email`); `User::twoFactorEnabled(): bool`, `User::twoFactorMethod(): ?string`, `User::isAdmin(): bool`, `User::$fillable` + casts extended.

- [ ] **Step 1: Install packages**

Run: `composer require laravel/socialite pragmarx/google2fa-laravel simplesoftwareio/simple-qrcode`
Expected: installed without errors.

- [ ] **Step 2: Write the failing tests** (append to `Modules/IdentityAccess/tests/Feature/UserDetailsTest.php`)

```php
public function test_two_factor_type_enum_round_trips(): void
{
    $user = User::factory()->create(['two_factor_type' => 'totp', 'two_factor_confirmed_at' => now()]);

    $this->assertTrue($user->twoFactorEnabled());
    $this->assertSame('totp', $user->twoFactorMethod());
}

public function test_two_factor_helpers_for_disabled_user(): void
{
    $user = User::factory()->create();

    $this->assertFalse($user->twoFactorEnabled());
    $this->assertNull($user->twoFactorMethod());
}

public function test_is_admin_helper(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);

    $this->assertTrue($admin->isAdmin());
    $this->assertFalse($user->isAdmin());
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/UserDetailsTest.php`
Expected: FAIL (column `two_factor_type` does not exist / method undefined).

- [ ] **Step 4: Write the migration**

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
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('password')->nullable()->change();
            $table->text('two_factor_secret')->nullable();
            $table->string('two_factor_type')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar', 'two_factor_secret', 'two_factor_type', 'two_factor_confirmed_at']);
        });
    }
};
```

- [ ] **Step 5: Write the enum**

```php
<?php

namespace Modules\IdentityAccess\Enums;

enum TwoFactorType: string
{
    case Totp = 'totp';
    case Email = 'email';
}
```

- [ ] **Step 6: Extend the User model** (`Modules/IdentityAccess/app/Models/User.php`)

In `$fillable` add: `'google_id', 'avatar', 'two_factor_secret', 'two_factor_type', 'two_factor_confirmed_at'`.
In `casts()` add:

```php
'two_factor_secret' => 'encrypted',
'two_factor_type' => \Modules\IdentityAccess\Enums\TwoFactorType::class,
```

Add helper methods at the end of the class:

```php
public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function twoFactorEnabled(): bool
{
    return $this->two_factor_type !== null && $this->two_factor_confirmed_at !== null;
}

public function twoFactorMethod(): ?string
{
    return $this->two_factor_type?->value;
}
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/UserDetailsTest.php`
Expected: PASS (all tests, old + new).

- [ ] **Step 8: Commit**

```bash
git add composer.json composer.lock \
  Modules/IdentityAccess/database/migrations/2026_08_19_000004_add_2fa_and_google_to_users_table.php \
  Modules/IdentityAccess/app/Enums/TwoFactorType.php \
  Modules/IdentityAccess/app/Models/User.php \
  Modules/IdentityAccess/tests/Feature/UserDetailsTest.php
git commit -m "feat: 2FA/Google foundation — migration, enum, user model helpers"
```

---

### Task 2: 2FA enrollment (settings card)

**Files:**
- Create: `Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php`
- Create: `Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php`
- Modify: `Modules/IdentityAccess/resources/views/users/settings.blade.php` (slot the card after Account Details)
- Modify: `Modules/IdentityAccess/routes/web.php`
- Modify: `resources/css/app.css` (twofa- styles)
- Test: `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` (enrollment group)

**Interfaces:**
- Consumes: `User::twoFactorEnabled()`, `TwoFactorType` enum, `Google2FA` facade (`Pragmarx\Google2FALaravel\Facades\Google2FA`), `QrCode` facade (`SimpleSoftwareIO\QrCode\Facades\QrCode`).
- Produces routes: `POST /profile/settings/twofa/enable-totp`, `POST /profile/settings/twofa/enable-email`, `GET /profile/settings/twofa/qr`, `POST /profile/settings/twofa/confirm`, `POST /profile/settings/twofa/disable` (names `profile.settings.twofa.*`); session keys `twofa.pending_type`, `twofa.confirm_attempts`.

- [ ] **Step 1: Write the failing tests** (create `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`)

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['status' => 'active', 'role' => 'user']);
    }

    private function enableTotp(User $user): void
    {
        $this->actingAs($user)->post('/profile/settings/twofa/enable-totp', ['password' => 'password'])
            ->assertRedirect();
    }

    public function test_enable_totp_requires_current_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/profile/settings/twofa/enable-totp', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_enable_totp_then_confirm_with_valid_code(): void
    {
        $user = $this->user();
        $this->enableTotp($user);

        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $code = \Pragmarx\Google2FALaravel\Facades\Google2FA::getCurrentOtp($secret);

        $this->actingAs($user)->post('/profile/settings/twofa/confirm', ['code' => $code])
            ->assertRedirect();

        $fresh = $user->fresh();
        $this->assertSame('totp', $fresh->two_factor_type);
        $this->assertNotNull($fresh->two_factor_confirmed_at);
    }

    public function test_enable_totp_confirm_rejects_wrong_code(): void
    {
        $user = $this->user();
        $this->enableTotp($user);

        $this->actingAs($user)->post('/profile/settings/twofa/confirm', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->two_factor_type);
    }

    public function test_confirm_locks_out_after_five_failures(): void
    {
        $user = $this->user();
        $this->enableTotp($user);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post('/profile/settings/twofa/confirm', ['code' => '000000']);
        }

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_type);
    }

    public function test_disable_requires_password_and_clears_2fa(): void
    {
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
            'two_factor_type' => 'totp',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user)->post('/profile/settings/twofa/disable', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)->post('/profile/settings/twofa/disable', ['password' => 'password'])
            ->assertRedirect();

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_type);
        $this->assertNull($fresh->two_factor_confirmed_at);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: FAIL (routes missing → 404).

- [ ] **Step 3: Write the controller**

`Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php`:

```php
<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Mail\OtpMail;
use Modules\IdentityAccess\Models\User;
use Pragmarx\Google2FALaravel\Facades\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    private const OTP_TTL = 600;
    private const MAX_CONFIRM_ATTEMPTS = 5;

    /* ---------- enrollment: TOTP ---------- */

    public function enableTotp(Request $request)
    {
        $data = $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Current password is incorrect.']);
        }
        if ($user->twoFactorEnabled()) {
            return back()->withErrors(['twofa' => 'Two-factor authentication is already enabled.']);
        }

        $user->forceFill(['two_factor_secret' => Google2FA::generateSecretKey()])->save();
        session(['twofa.pending_type' => 'totp']);

        return back()->with('status', 'Scan the QR code with your authenticator app, then confirm with a code.');
    }

    public function qr(Request $request)
    {
        $user = $request->user();
        if ($user->twoFactorEnabled() || ! $user->two_factor_secret || session('twofa.pending_type') !== 'totp') {
            abort(404);
        }

        $url = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode(config('app.name')),
            rawurlencode($user->email),
            $user->two_factor_secret,
            rawurlencode(config('app.name'))
        );

        $svg = QrCode::format('svg')->size(220)->generate($url);

        return response($svg)->header('Content-Type', 'image/svg+xml')->header('Cache-Control', 'no-store');
    }

    /* ---------- enrollment: email ---------- */

    public function enableEmail(Request $request)
    {
        $data = $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Current password is incorrect.']);
        }
        if ($user->twoFactorEnabled()) {
            return back()->withErrors(['twofa' => 'Two-factor authentication is already enabled.']);
        }

        $code = $this->issueOtp($user);
        Mail::to($user)->queue(new OtpMail($user, $code));
        session(['twofa.pending_type' => 'email']);

        return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
    }

    /* ---------- confirmation ---------- */

    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:10']);
        $user = $request->user();
        $pending = session('twofa.pending_type');

        if (! $pending || ($pending === 'totp' && ! $user->two_factor_secret)) {
            return back()->withErrors(['code' => 'No pending 2FA setup. Start again.']);
        }

        $valid = $pending === 'totp'
            ? Google2FA::verifyKey($user->two_factor_secret, trim($data['code']), 1)
            : $this->checkOtp($user, trim($data['code']));

        if (! $valid) {
            $attempts = (int) session('twofa.confirm_attempts', 0) + 1;
            session(['twofa.confirm_attempts' => $attempts]);
            if ($attempts >= self::MAX_CONFIRM_ATTEMPTS) {
                $user->forceFill(['two_factor_secret' => null])->save();
                session()->forget(['twofa.pending_type', 'twofa.confirm_attempts']);
                return back()->withErrors(['code' => 'Too many invalid attempts. 2FA setup was reset — start again.']);
            }
            return back()->withErrors(['code' => 'The code is invalid or has expired.']);
        }

        $user->forceFill([
            'two_factor_type' => $pending,
            'two_factor_confirmed_at' => now(),
        ])->save();
        session()->forget(['twofa.pending_type', 'twofa.confirm_attempts', '2fa.required']);
        Log::info('auth.2fa_enabled', ['user' => $user->id, 'method' => $pending]);

        return back()->with('status', 'Two-factor authentication is now active.');
    }

    /* ---------- disable ---------- */

    public function disable(Request $request)
    {
        $data = $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Current password is incorrect.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_type' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        if ($user->isAdmin()) {
            session(['2fa.required' => true]);
        }
        Log::info('auth.2fa_disabled', ['user' => $user->id]);

        return back()->with('status', 'Two-factor authentication disabled.');
    }

    /* ---------- OTP helpers ---------- */

    private function issueOtp(User $user): string
    {
        $code = (string) random_int(100000, 999999);
        Cache::put('2fa:otp:' . $user->id, Hash::make($code), self::OTP_TTL);
        return $code;
    }

    private function checkOtp(User $user, string $code): bool
    {
        $hashed = Cache::get('2fa:otp:' . $user->id);
        if (! $hashed || ! Hash::check($code, $hashed)) {
            return false;
        }
        Cache::forget('2fa:otp:' . $user->id);
        return true;
    }
}
```

- [ ] **Step 4: Add routes** (`Modules/IdentityAccess/routes/web.php` — inside the existing `auth` group)

```php
    Route::post('/profile/settings/twofa/enable-totp', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'enableTotp'])->middleware('throttle:2fa-enroll')->name('profile.settings.twofa.enable-totp');
    Route::post('/profile/settings/twofa/enable-email', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'enableEmail'])->middleware('throttle:2fa-enroll')->name('profile.settings.twofa.enable-email');
    Route::get('/profile/settings/twofa/qr', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'qr'])->name('profile.settings.twofa.qr');
    Route::post('/profile/settings/twofa/confirm', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'confirm'])->middleware('throttle:2fa-enroll')->name('profile.settings.twofa.confirm');
    Route::post('/profile/settings/twofa/disable', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'disable'])->middleware('throttle:2fa-enroll')->name('profile.settings.twofa.disable');
```

- [ ] **Step 5: Write the card partial**

`Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php` (uses existing `profile-card` / `form-group` / `form-input` / `btn` classes + new `twofa-*` classes):

```blade
<div class="profile-card">
    <h2 class="pc-card__title">Two-Factor Authentication</h2>

    @if ($user->twoFactorEnabled())
        <p class="twofa-status">Enabled via
            <span class="twofa-badge">{{ $user->twoFactorMethod() === 'totp' ? 'Authenticator App' : 'Email Codes' }}</span>
            since {{ $user->two_factor_confirmed_at->format('M j, Y') }}.
        </p>

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

        @if (session('twofa.pending_type') === 'totp')
            <div class="twofa-setup">
                <img src="{{ route('profile.settings.twofa.qr') }}" alt="Authenticator setup QR code" class="twofa-qr">
                <p class="twofa-hint">Scan this QR with Google Authenticator or any TOTP app, then enter the 6-digit code below.</p>
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
        @elseif (session('twofa.pending_type') === 'email')
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
            <div class="twofa-options">
                <form action="{{ route('profile.settings.twofa.enable-totp') }}" method="POST" class="twofa-option">
                    @csrf
                    <p class="twofa-option-title">Authenticator App</p>
                    <p class="twofa-option-desc">Use Google Authenticator or any TOTP app. Works offline.</p>
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enable Authenticator App</button>
                </form>
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
            </div>
        @endif

        @if ($errors->has('twofa'))
            <p class="form-error">{{ $errors->first('twofa') }}</p>
        @endif
    @endif
</div>
```

- [ ] **Step 6: Slot the card into settings** (`Modules/IdentityAccess/resources/views/users/settings.blade.php` — after the closing `</section>` of Account Details)

```blade
<section class="profile-section">
    @include('identityaccess::partials.twofa-card', ['user' => $user])
</section>
```

- [ ] **Step 7: Add CSS** (`resources/css/app.css` — new block before the PAGINATION block)

```css
/* ============================================================
   2FA (auth module)
   ============================================================ */

.twofa-status { color: var(--text-600); margin-bottom: 1.25rem; line-height: 1.6; }
.twofa-badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 99px; background: var(--brand-accent); color: #fff; font-size: 0.8rem; font-weight: 700; }
.twofa-form { max-width: 420px; }
.twofa-setup { max-width: 420px; }
.twofa-qr { display: block; width: 200px; height: 200px; margin-bottom: 1rem; border-radius: 0.75rem; border: 1px solid var(--border); background: #fff; }
.twofa-hint { color: var(--text-600); margin-bottom: 1rem; line-height: 1.6; }
.twofa-options { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.twofa-option { border: 1px solid var(--border); border-radius: 1rem; padding: 1.25rem; background: var(--surface-100); }
.twofa-option-title { font-weight: 800; color: var(--text-900); margin-bottom: 0.25rem; }
.twofa-option-desc { color: var(--text-600); font-size: 0.85rem; margin-bottom: 1rem; line-height: 1.5; }
.btn-danger { background: transparent; border: 1px solid #dc2626; color: #dc2626; }
.btn-danger:hover { background: #dc2626; color: #fff; }

@media (max-width: 768px) {
    .twofa-options { grid-template-columns: 1fr; }
}
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: PASS (5 tests). Note: `enableEmail` references `OtpMail` but PHP resolves it only at runtime, so the mailable can ship in Task 3.

- [ ] **Step 9: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php \
  Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php \
  Modules/IdentityAccess/resources/views/users/settings.blade.php \
  Modules/IdentityAccess/routes/web.php resources/css/app.css \
  Modules/IdentityAccess/tests/Feature/TwoFactorTest.php
git commit -m "feat: 2FA enrollment — TOTP + email setup, QR, confirm, disable"
```

---

### Task 3: Challenge flow

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/AuthController.php` (`login()`)
- Create: `Modules/IdentityAccess/app/Http/Middleware/Ensure2faChallenge.php`
- Modify: `bootstrap/app.php` (alias `2fa.pending`)
- Create: `Modules/IdentityAccess/app/Mail/OtpMail.php`
- Create: `Modules/IdentityAccess/resources/views/emails/otp.blade.php`
- Create: `Modules/IdentityAccess/resources/views/auth/challenge.blade.php`
- Modify: `Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php` (add `challenge`, `verify`, `resend`)
- Modify: `Modules/IdentityAccess/routes/web.php`
- Test: `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` (challenge group)

**Interfaces:**
- Consumes: `TwoFactorController::enableTotp` session pattern, `User::twoFactorEnabled()`.
- Produces: session keys `2fa.pending` (user id), `2fa.attempts`, `2fa.pending_method`; routes `2fa.challenge` (GET), `2fa.verify` (POST), `2fa.resend` (POST); middleware alias `2fa.pending`; `OtpMail($user, $code)`.

- [ ] **Step 1: Write the failing tests** (append to `TwoFactorTest.php`)

```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Modules\IdentityAccess\Mail\OtpMail;

public function test_password_login_with_2fa_redirects_to_challenge(): void
{
    $user = User::factory()->create([
        'status' => 'active', 'role' => 'user',
        'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
        'two_factor_type' => 'totp',
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);
    $response->assertRedirect(route('2fa.challenge'));
    $this->assertGuest();
    $this->assertSame($user->id, session('2fa.pending'));
}

public function test_challenge_verify_with_totp_logs_in(): void
{
    $secret = \Pragmarx\Google2FALaravel\Facades\Google2FA::generateSecretKey();
    $user = User::factory()->create([
        'status' => 'active', 'role' => 'user',
        'two_factor_secret' => $secret,
        'two_factor_type' => 'totp',
        'two_factor_confirmed_at' => now(),
    ]);
    $code = \Pragmarx\Google2FALaravel\Facades\Google2FA::getCurrentOtp($secret);

    $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);
    $this->post('/2fa/challenge/verify', ['code' => $code])
        ->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    $this->assertNull(session('2fa.pending'));
}

public function test_challenge_wrong_code_after_five_attempts_invalidates_session(): void
{
    $user = User::factory()->create([
        'status' => 'active', 'role' => 'user',
        'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
        'two_factor_type' => 'totp',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);

    for ($i = 0; $i < 4; $i++) {
        $this->post('/2fa/challenge/verify', ['code' => '000000'])->assertSessionHasErrors('code');
    }
    $this->post('/2fa/challenge/verify', ['code' => '000000'])->assertRedirect(route('login'));
    $this->assertGuest();
    $this->assertNull(session('2fa.pending'));
}

public function test_email_method_challenge_with_queued_code(): void
{
    Mail::fake();
    $user = User::factory()->create([
        'status' => 'active', 'role' => 'user',
        'two_factor_type' => 'email',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);

    $code = '';
    Mail::assertQueued(OtpMail::class, function (OtpMail $mail) use (&$code) {
        $code = $mail->code;
        return true;
    });
    $this->assertNotSame('', $code);

    $this->post('/2fa/challenge/verify', ['code' => $code])
        ->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
}

public function test_resend_respects_cooldown(): void
{
    Mail::fake();
    $user = User::factory()->create([
        'status' => 'active', 'role' => 'user',
        'two_factor_type' => 'email',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);
    $this->post('/2fa/challenge/resend')->assertRedirect();
    Mail::assertQueued(OtpMail::class, 2);

    $this->post('/2fa/challenge/resend');
    Mail::assertQueued(OtpMail::class, 2);
}

public function test_google_only_account_cannot_login_with_password(): void
{
    $user = User::factory()->create([
        'status' => 'active', 'role' => 'user',
        'password' => null,
        'google_id' => 'g-123',
    ]);

    $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: FAIL (routes/methods missing).

- [ ] **Step 3: Rewrite `AuthController::login()`**

Replace the `Auth::attempt(...)` block (and the inactive-user check stays as-is) with:

```php
        if (! $user || $user->password === null) {
            return back()->withErrors([
                'email' => $user ? 'This account uses Google sign-in.' : 'Invalid credentials',
            ]);
        }

        if (! Auth::validate($credentials)) {
            return back()->withErrors(['email' => 'Invalid credentials']);
        }

        if ($user->twoFactorEnabled()) {
            session([
                '2fa.pending' => $user->id,
                '2fa.attempts' => 0,
                '2fa.pending_method' => $user->twoFactorMethod(),
            ]);
            return redirect()->route('2fa.challenge');
        }

        Auth::login($user);
        $request->session()->regenerate();
        if ($user->isAdmin()) {
            session(['2fa.required' => true]);
        }

        return redirect()->intended('/');
```

Note: admin without 2FA now always gets the `2fa.required` flag at login (enforced in Task 4).

- [ ] **Step 4: Write the mailable + view**

`Modules/IdentityAccess/app/Mail/OtpMail.php`:

```php
<?php

namespace Modules\IdentityAccess\Mail;

use Modules\IdentityAccess\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $code;

    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your LUWI verification code');
    }

    public function content(): Content
    {
        return new Content(view: 'identityaccess::emails.otp');
    }
}
```

`Modules/IdentityAccess/resources/views/emails/otp.blade.php`:

```blade
<x-mail::message>
# Your Verification Code

Hi {{ $user->name }},

Use the code below to complete your sign-in:

# {{ $code }}

This code expires in **10 minutes**. If you did not request it, you can safely ignore this email.

Regards,<br>
The SmartShop Security Team
</x-mail::message>
```

- [ ] **Step 5: Write the challenge middleware**

`Modules/IdentityAccess/app/Http/Middleware/Ensure2faChallenge.php`:

```php
<?php

namespace Modules\IdentityAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Ensure2faChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            session()->forget('2fa.pending');
            return redirect('/');
        }

        if (! session('2fa.pending')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
```

Register alias in `bootstrap/app.php` (`$middleware->alias([...])`):

```php
        '2fa.pending' => \Modules\IdentityAccess\Http\Middleware\Ensure2faChallenge::class,
```

- [ ] **Step 6: Add challenge routes + controller methods**

Routes (outside the `auth` group — pending users are NOT authenticated):

```php
Route::middleware(['2fa.pending'])->group(function () {
    Route::get('/2fa/challenge', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge/verify', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'verify'])->middleware('throttle:2fa')->name('2fa.verify');
    Route::post('/2fa/challenge/resend', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'resend'])->middleware('throttle:2fa-resend')->name('2fa.resend');
});
```

Controller additions:

```php
    public function challenge()
    {
        $userId = session('2fa.pending');
        $user = User::find($userId);

        if (! $user || ! $user->twoFactorEnabled()) {
            session()->forget('2fa.pending');
            return redirect()->route('login');
        }

        return view('identityaccess::auth.challenge', [
            'user' => $user,
            'method' => session('2fa.pending_method', $user->twoFactorMethod()),
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:10']);
        $userId = session('2fa.pending');
        $user = User::find($userId);

        if (! $user || ! $user->twoFactorEnabled()) {
            session()->forget('2fa.pending');
            return redirect()->route('login');
        }

        $method = session('2fa.pending_method', $user->twoFactorMethod());
        $valid = $method === 'email'
            ? $this->checkOtp($user, trim($data['code']))
            : $this->verifyTotp($user, trim($data['code']));

        if (! $valid) {
            $attempts = (int) session('2fa.attempts', 0) + 1;
            session(['2fa.attempts' => $attempts]);
            if ($attempts >= 5) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors(['code' => 'Too many invalid attempts. Please sign in again.']);
            }
            return back()->withErrors(['code' => 'The code is invalid or has expired.']);
        }

        $request->session()->forget(['2fa.pending', '2fa.attempts', '2fa.pending_method', '2fa:otp:' . $user->id]);
        Auth::login($user);
        $request->session()->regenerate();
        Log::info('auth.2fa_challenge', ['user' => $user->id, 'method' => $method]);

        if ($user->isAdmin() && ! $user->twoFactorEnabled()) {
            session(['2fa.required' => true]);
            return redirect()->route('profile.settings')->with('status', 'Two-factor authentication is required for admin accounts. Please enable it.');
        }

        return redirect()->intended('/');
    }

    public function resend(Request $request)
    {
        $userId = session('2fa.pending');
        $user = User::find($userId);

        if (! $user || ! $user->twoFactorEnabled()) {
            return redirect()->route('login');
        }

        if (Cache::has('2fa:resend:' . $user->id)) {
            return back()->withErrors(['code' => 'Please wait a moment before requesting another code.']);
        }

        $code = $this->issueOtp($user);
        Mail::to($user)->queue(new OtpMail($user, $code));
        Cache::put('2fa:resend:' . $user->id, true, 60);

        return back()->with('status', 'A new verification code was sent to your email.');
    }

    private function verifyTotp(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }
        $timestamp = Google2FA::verifyKeyNewer($user->two_factor_secret, $code, (int) Cache::get('2fa:totp-ts:' . $user->id, 0));
        if ($timestamp === false) {
            return false;
        }
        Cache::put('2fa:totp-ts:' . $user->id, $timestamp, self::OTP_TTL);
        return true;
    }
```

- [ ] **Step 7: Write the challenge view**

`Modules/IdentityAccess/resources/views/auth/challenge.blade.php`:

```blade
@section('title', 'Verify Your Identity | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card auth-card--narrow">
        <h1 class="auth-title">Verify Your Identity</h1>
        <p class="auth-subtitle">Enter the code to complete your sign-in.</p>

        @if ($method === 'email')
            <p class="twofa-hint">A 6-digit code was sent to <strong>{{ $user->email }}</strong>. It expires in 10 minutes.</p>
            <form method="POST" action="{{ route('2fa.resend') }}" class="twofa-form">
                @csrf
                <button type="submit" class="btn btn-ghost">Resend Code</button>
            </form>
        @else
            <p class="twofa-hint">Open your authenticator app and enter the current 6-digit code.</p>
        @endif

        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Verification Code</label>
                <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                @error('code') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            @if (session('status'))
                <p class="form-success">{{ session('status') }}</p>
            @endif

            <button type="submit" class="auth-button">Verify &amp; Sign In</button>
        </form>

        <p class="switch-auth">
            <a href="{{ url('/logout') }}">Not you? Sign out</a>
        </p>
    </div>
</div>
</x-app-layout>
```

Check `resources/css/app.css` for existing `.form-error`/`.form-success` classes — add if missing (`.form-error { color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem; }`).

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: PASS (11 tests).

- [ ] **Step 9: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/AuthController.php \
  Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php \
  Modules/IdentityAccess/app/Http/Middleware/Ensure2faChallenge.php \
  bootstrap/app.php \
  Modules/IdentityAccess/app/Mail/OtpMail.php \
  Modules/IdentityAccess/resources/views/emails/otp.blade.php \
  Modules/IdentityAccess/resources/views/auth/challenge.blade.php \
  Modules/IdentityAccess/routes/web.php \
  Modules/IdentityAccess/tests/Feature/TwoFactorTest.php
git commit -m "feat: 2FA challenge flow — login deferral, code verify/resend, lockout"
```

---

### Task 4: Admin enforcement + throttles + audit

**Files:**
- Create: `Modules/IdentityAccess/app/Http/Middleware/Ensure2faEnrolled.php`
- Modify: `bootstrap/app.php` (alias `2fa.enrolled`)
- Modify: `Modules/IdentityAccess/routes/web.php` (admin group)
- Modify: `Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php` (throttles)
- Test: `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` (admin group) + `Modules/TelemetryPipeline/tests/Feature/RateLimiterTest.php` (throttle presence)

**Interfaces:**
- Consumes: session flag `2fa.required` (set in Tasks 2/3).
- Produces: middleware alias `2fa.enrolled`; throttles `2fa`, `2fa-resend`, `2fa-enroll` (defined here; used in Tasks 2/3 routes — define BEFORE those tasks are merged, i.e. now).

- [ ] **Step 1: Write the failing tests** (append to `TwoFactorTest.php`)

```php
public function test_admin_without_2fa_is_gated_from_admin_pages(): void
{
    $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);

    $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect('/');
    $this->assertTrue(session('2fa.required'));

    $this->get('/admin/dashboard')->assertRedirect(route('profile.settings'));
    $this->get('/admin/users')->assertRedirect(route('profile.settings'));
}

public function test_admin_2fa_flag_cleared_after_enrollment(): void
{
    $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);

    $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
    $this->assertTrue(session('2fa.required'));

    $this->actingAs($admin)->post('/profile/settings/twofa/enable-totp', ['password' => 'password']);
    $secret = $admin->fresh()->two_factor_secret;
    $code = \Pragmarx\Google2FALaravel\Facades\Google2FA::getCurrentOtp($secret);
    $this->actingAs($admin)->post('/profile/settings/twofa/confirm', ['code' => $code])->assertRedirect();

    $this->assertNull(session('2fa.required'));
    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
}

public function test_acting_as_admin_unaffected_by_gate(): void
{
    $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php`
Expected: FAIL (admin gate not wired — `/admin/dashboard` returns 200 without flag).

- [ ] **Step 3: Write the middleware**

`Modules/IdentityAccess/app/Http/Middleware/Ensure2faEnrolled.php`:

```php
<?php

namespace Modules\IdentityAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Ensure2faEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isAdmin() && session('2fa.required')) {
            return redirect()->route('profile.settings')
                ->with('status', 'Two-factor authentication is required for admin accounts. Please enable it to continue.');
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php`:

```php
        '2fa.enrolled' => \Modules\IdentityAccess\Http\Middleware\Ensure2faEnrolled::class,
```

- [ ] **Step 4: Apply to the admin group** (`Modules/IdentityAccess/routes/web.php`)

```php
Route::middleware(['auth', 'admin', '2fa.enrolled'])->prefix('admin')->as('admin.')->group(function () {
```

- [ ] **Step 5: Add the throttles** (`Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php`, inside `boot()`)

```php
        RateLimiter::for('2fa', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('2fa-resend', fn (Request $request) => Limit::perMinute(1)->by($request->ip()));
        RateLimiter::for('2fa-enroll', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/TwoFactorTest.php Modules/TelemetryPipeline/tests/Feature/RateLimiterTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Middleware/Ensure2faEnrolled.php \
  bootstrap/app.php Modules/IdentityAccess/routes/web.php \
  Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php \
  Modules/IdentityAccess/tests/Feature/TwoFactorTest.php
git commit -m "feat: mandatory 2FA for admins — session gate + throttles"
```

---

### Task 5: Google OAuth

**Files:**
- Create: `Modules/IdentityAccess/app/Http/Controllers/GoogleAuthController.php`
- Modify: `config/services.php` (google block)
- Modify: `.env` + `.env.example` (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI)
- Modify: `Modules/IdentityAccess/routes/web.php`
- Create: `Modules/IdentityAccess/resources/views/partials/auth-google.blade.php`
- Modify: `Modules/IdentityAccess/resources/views/auth/login.blade.php` + `signup.blade.php`
- Modify: `Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php` (Google connect section) — or a sibling partial
- Modify: `resources/css/app.css` (auth-google styles)
- Test: `Modules/IdentityAccess/tests/Feature/GoogleAuthTest.php`

**Interfaces:**
- Consumes: `2fa.pending` session, `User` helpers.
- Produces: routes `auth.google.redirect`, `auth.google.callback`; `Socialite::fake()` compatible callback.

- [ ] **Step 1: Write the failing tests** (create `Modules/IdentityAccess/tests/Feature/GoogleAuthTest.php`)

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Modules\IdentityAccess\Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $email, string $googleId = 'g-1', string $name = 'G Ogle'): void
    {
        $abstract = new \Laravel\Socialite\Two\User();
        $abstract->map(['id' => $googleId, 'name' => $name, 'email' => $email, 'avatar' => 'https://example.com/avatar.png']);
        $abstract->token = 'token';
        $abstract->refreshToken = 'refresh';

        Socialite::shouldReceive('driver')->with('google')->andReturn(
            new class($abstract) {
                public $user;
                public function __construct($user) { $this->user = $user; }
                public function redirect() { return redirect('/auth/google/callback'); }
                public function user() { return $this->user; }
            }
        );
    }

    public function test_google_login_creates_new_account(): void
    {
        $this->fakeGoogleUser('new.person@example.com');

        $response = $this->get('/auth/google/redirect');
        $response->assertRedirect('/auth/google/callback');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $user = User::where('email', 'new.person@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('g-1', $user->google_id);
        $this->assertNull($user->password);
        $this->assertSame('user', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_links_existing_account(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email' => 'existing@example.com', 'password' => 'password']);
        $this->fakeGoogleUser('existing@example.com', 'g-99');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertSame('g-99', $user->fresh()->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_with_2fa_goes_to_challenge(): void
    {
        $user = User::factory()->create([
            'status' => 'active', 'email' => 'twofa@example.com',
            'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
            'two_factor_type' => 'totp',
            'two_factor_confirmed_at' => now(),
        ]);
        $this->fakeGoogleUser('twofa@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();
        $this->assertSame($user->id, session('2fa.pending'));
    }

    public function test_google_login_with_conflicting_link_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email' => 'linked@example.com', 'google_id' => 'g-original']);
        $this->fakeGoogleUser('linked@example.com', 'g-other');

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame('g-original', $user->fresh()->google_id);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/GoogleAuthTest.php`
Expected: FAIL (routes missing).

- [ ] **Step 3: Write the controller**

`Modules/IdentityAccess/app/Http/Controllers/GoogleAuthController.php`:

```php
<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Modules\IdentityAccess\Models\User;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('auth.google_callback_failed', ['error' => $e->getMessage()]);
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $email = $googleUser->getEmail();
        $existing = $email ? User::where('email', $email)->first() : null;

        if ($existing && $existing->google_id && $existing->google_id !== $googleUser->getId()) {
            Log::warning('auth.google_email_conflict', ['user' => $existing->id]);
            return redirect()->route('login')->withErrors(['email' => 'This email is already linked to a different Google account.']);
        }

        if ($existing) {
            $user = $existing;
            if (! $user->google_id) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                ])->save();
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Member',
                'email' => $email,
                'password' => null,
                'role' => 'user',
                'status' => 'active',
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        }

        Log::info('auth.google_linked', ['user' => $user->id]);

        if ($user->twoFactorEnabled()) {
            session([
                '2fa.pending' => $user->id,
                '2fa.attempts' => 0,
                '2fa.pending_method' => $user->twoFactorMethod(),
            ]);
            return redirect()->route('2fa.challenge');
        }

        Auth::login($user);
        $request->session()->regenerate();
        if ($user->isAdmin()) {
            session(['2fa.required' => true]);
        }

        return redirect()->intended('/');
    }
}
```

- [ ] **Step 4: Config + env**

`config/services.php` — add to the returned array:

```php
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
```

`.env` + `.env.example` — append:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://smartshop-luwi.tech/auth/google/callback
```

- [ ] **Step 5: Routes** (`Modules/IdentityAccess/routes/web.php`)

```php
Route::get('/auth/google/redirect', [\Modules\IdentityAccess\Http\Controllers\GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [\Modules\IdentityAccess\Http\Controllers\GoogleAuthController::class, 'handleCallback'])->name('auth.google.callback');
```

- [ ] **Step 6: Google button partial + views**

`Modules/IdentityAccess/resources/views/partials/auth-google.blade.php`:

```blade
<div class="auth-or-divider"><span>or</span></div>

<a href="{{ route('auth.google.redirect') }}" class="auth-google-btn">
    <svg class="auth-google-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18A10.96 10.96 0 0 0 1 12c0 1.77.43 3.45 1.18 4.94l3.66-2.84z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
    </svg>
    Continue with Google
</a>
```

Slot into `login.blade.php` (after the `</form>`) and `signup.blade.php` (after its `</form>`):

```blade
@include('identityaccess::partials.auth-google')
```

CSS (`resources/css/app.css`, same 2FA block area):

```css
.auth-or-divider { display: flex; align-items: center; gap: 1rem; margin: 1.5rem 0; color: var(--text-400); font-size: 0.85rem; }
.auth-or-divider::before, .auth-or-divider::after { content: ""; flex: 1; height: 1px; background: var(--border); }
.auth-google-btn { display: flex; align-items: center; justify-content: center; gap: 0.75rem; width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border); border-radius: 0.75rem; background: var(--surface-100); color: var(--text-900); font-weight: 700; text-decoration: none; transition: all 0.3s ease; }
.auth-google-btn:hover { border-color: var(--brand-primary); box-shadow: var(--shadow-sm); }
.auth-google-icon { width: 20px; height: 20px; }
```

- [ ] **Step 7: Settings — Google connect section**

In `twofa-card.blade.php`, after the main card content, add a Google connect block:

```blade
<div class="profile-card">
    <h2 class="pc-card__title">Google Account</h2>
    @if ($user->google_id)
        <p class="twofa-status">
            Connected as
            @if ($user->avatar) <img src="{{ $user->avatar }}" alt="" class="twofa-google-avatar"> @endif
            <strong>{{ $user->email }}</strong>.
        </p>
    @else
        <p class="twofa-status">Link your Google account to sign in without a password.</p>
        <a href="{{ route('auth.google.redirect') }}" class="btn btn-primary">Connect Google Account</a>
    @endif
</div>
```

CSS: `.twofa-google-avatar { width: 28px; height: 28px; border-radius: 50%; vertical-align: middle; margin-right: 0.5rem; }`

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/GoogleAuthTest.php`
Expected: PASS (4 tests).

- [ ] **Step 9: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/GoogleAuthController.php \
  config/services.php .env.example \
  Modules/IdentityAccess/routes/web.php \
  Modules/IdentityAccess/resources/views/partials/auth-google.blade.php \
  Modules/IdentityAccess/resources/views/auth/login.blade.php \
  Modules/IdentityAccess/resources/views/auth/signup.blade.php \
  Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php \
  resources/css/app.css \
  Modules/IdentityAccess/tests/Feature/GoogleAuthTest.php
git commit -m "feat: Google OAuth sign-in — auto-link, auto-create, 2FA integration"
```

---

### Task 6: Full verification

- [ ] **Step 1: Full suite**

Run: `php artisan test`
Expected: all suites PASS (48 existing + new: UserDetailsTest +3, TwoFactorTest +11, GoogleAuthTest +4).

- [ ] **Step 2: Build + convention sweep**

```bash
npm run build
grep -rn "style=" Modules/*/resources/views/ resources/views/ --include="*.blade.php" | grep -v "mail\|email" | wc -l   # expect 0
grep -rn "<style>" Modules/*/resources/views/ resources/views/ --include="*.blade.php" | grep -v "emails" | wc -l   # expect 0
```

- [ ] **Step 3: Live CDP audit (challenge page + settings card)** — via agent-browser against :8001:
  - Enable TOTP for a test user through the real UI; confirm; disable.
  - Login of a 2FA user → challenge page at 390/768/1280 — no horizontal overflow (same probe as the responsive pass).
  - Settings page with the 2FA card at 390/768/1280 — no overflow, `.twofa-options` collapses to 1 column ≤768px.
  - `/login` with Google button — button renders, no overflow.

- [ ] **Step 4: Commit any fixes** (conventional message, explicit staging).

---

### Task 7: Deploy to production

- [ ] **Step 1:** `git push origin main`
- [ ] **Step 2:** On server (`ssh root@104.248.163.215`): `cd /var/www/smartshop && git pull origin main && composer install --no-interaction && php artisan migrate --force && npm ci && npm run build && php artisan optimize:clear && php artisan queue:restart`
- [ ] **Step 3:** Live smoke (HTTPS via Host header or domain):
  - `curl -sk -o /dev/null -w '%{http_code}' https://smartshop-luwi.tech/login` → 200; page contains "Continue with Google" and the Google button routes to `/auth/google/redirect`.
  - Admin `admin@test.com` login → expect redirect to `/profile/settings` (2FA required gate) — VERIFY the gate shows on `/admin/dashboard`.
  - Buyer `user2@test.com` login → normal redirect `/`.
- [ ] **Step 4:** Note for user: add real Google OAuth credentials (Google Cloud Console) to server `.env` (`GOOGLE_CLIENT_ID/SECRET`, redirect `https://smartshop-luwi.tech/auth/google/callback`), then `php artisan config:cache` / restart. Until then the button renders and fails cleanly.
- [ ] **Step 5:** Update `PROJECT_REPORT.txt` (features + accounts + security section) and commit.