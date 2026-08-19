# 2FA + Google Sign-In — Full Design (2026-08-19)

Status: APPROVED by user (2026-08-19). Approach A: laravel/socialite + pragmarx/google2fa-laravel + simplesoftwareio/simple-qrcode, custom challenge flow in IdentityAccess.

## 1. Goal

Add production-grade two-factor authentication (TOTP authenticator app OR email one-time code, user's choice) and Google OAuth sign-up/login to the existing custom auth stack (`AuthController` / `/accessaccount`), without replacing it.

Production constraints (established in this project):
- Route names immutable; new routes only.
- No inline `style=` in blades (emails exempt).
- Module asset bundles load globally via `@vite` (app-layout) — module CSS class names must never collide with other modules (lesson learned from 0cec31e).
- Tests live in module test dirs (`Modules/*/tests`); existing tests use `actingAs` — the login-flow change must not break them.
- App is live at smartshop-luwi.tech: everything ships commit-by-commit, suite green, then deploys via push + server pull.

## 2. Architecture

All new code lives in `Modules/IdentityAccess`. Three new components:

| Component | Responsibility |
|---|---|
| `TwoFactorController` | Challenge page, verify, resend, enrollment (enable/confirm/disable), QR rendering |
| `GoogleAuthController` | OAuth redirect + callback (auto-link / auto-create / conflict) |
| `Ensure2faChallenge` + `Ensure2faEnrolled` middleware | Gate the pending-challenge session; gate admins without 2FA |

New packages: `laravel/socialite`, `pragmarx/google2fa-laravel`, `simplesoftwareio/simple-qrcode`.

## 3. Data model

Migration `2026_08_19_000004_add_2fa_and_google_to_users_table`:

| Column | Type | Notes |
|---|---|---|
| `google_id` | string nullable, unique | OAuth subject id |
| `avatar` | string nullable | Google picture URL |
| `password` | make nullable | Google-only accounts have no password |
| `two_factor_secret` | text nullable | encrypted via cast |
| `two_factor_type` | string nullable | `totp` \| `email` |
| `two_factor_confirmed_at` | timestamp nullable | enrollment proof (admin gate reads this) |

Enum `TwoFactorType` (`Modules\IdentityAccess\Enums`): cases `Totp`, `Email`. User model additions: fillable (`google_id`, `avatar`, `two_factor_secret`, `two_factor_type`, `two_factor_confirmed_at`), casts (`two_factor_secret => encrypted`, `two_factor_type => TwoFactorType`), helpers: `twoFactorEnabled()`, `twoFactorMethod()`, `hasGoogle()`, `usesGoogleOnly()`.

Existing columns untouched; no index changes beyond the unique `google_id`.

## 4. Login flow (password)

`AuthController::login`:
1. Validate + status check (unchanged).
2. `Auth::validate($credentials)` — NO auto-login.
3. If user `twoFactorEnabled()` → session `2fa.pending = user id`, redirect `GET /2fa/challenge`.
4. Else `Auth::login($user)` + session regenerate + `redirect()->intended('/')` (unchanged behavior).

`Ensure2faChallenge` middleware (alias `2fa.pending`, applied to challenge routes):
- If no `2fa.pending` in session → redirect `/` (or `/login` if guest).
- If session is authed AND `2fa.pending` exists → redirect away from challenge (challenge only for pending).

## 5. Challenge flow

`GET /2fa/challenge` — page shows the pending user's method (picker if both enabled: `totp` + `email`), code input, resend link for email method.

`POST /2fa/challenge/verify` (throttle `2fa`, 5/min/IP + per-session attempt cap):
- TOTP: `Google2FA::verifyKey($secret, $code, 1)` with replay protection via cache timestamp (`2fa:totp:{id}`) — new code must be newer than last verified.
- Email: 6-digit code stored **hashed** in cache `2fa:otp:{id}` (10 min TTL), `Hash::check`.
- Success: `Auth::login($user)`, session regenerate, forget `2fa.pending`, clear OTP cache, redirect `intended('/')`.
- Failure: increment `2fa.attempts`; at 5 → `session()->invalidate()` + regenerate, redirect `/login` with "too many attempts" error.
- If pending user is admin and not enrolled (`Ensure2faEnrolled` gate) → set `2fa.required` flag, redirect to settings enrollment card after login completes.

`POST /2fa/challenge/resend` (throttle `2fa-resend`, 1/min/IP): regenerates + queues a new email code; cooldown enforced by cache TTL (`2fa:resend:{id}`, 60s) so throttle + TTL double-guard.

Mailable `OtpMail` (ShouldQueue — database queue, worker running on server): 6-digit code, styled like existing `WelcomeMember`/order emails, expires in 10 minutes.

## 6. Enrollment (Settings page card)

`/profile/settings` gains a **"Two-Factor Authentication"** card (partial `partials/twofa-card.blade.php`, slotted into the existing settings layout next to the profile/security cards).

Routes (all `auth` + `throttle:2fa-enroll` where sensible):
- `POST /profile/settings/twofa/enable-totp` — requires current password. Generates secret (`Google2FA::generateSecretKey()`), stores it (encrypted), shows QR (SVG via `QrCode::format('svg')` — no GD dependency on the server), keeps status `pending_totp` in session until confirmed. GET variant serves the QR: `GET /profile/settings/twofa/qr` returns the SVG data URI for the pending secret.
- `POST /profile/settings/twofa/enable-email` — requires current password; queues code, sets `pending_email`, shows 6-digit input.
- `POST /profile/settings/twofa/confirm` — verifies pending code (TOTP or email per pending type) → sets `two_factor_type`, `two_factor_confirmed_at`, forgets pending, forgets `2fa.required`, success flash. Failed attempts capped (5 → reset pending, back to disabled).
- `POST /profile/settings/twofa/disable` — requires current password; clears secret/type/confirmed_at; if admin → sets `2fa.required` so they re-enroll before next admin access.

Password confirmation pattern: reuse the existing settings password field flow (current password validated against `Hash::check`), consistent with the profile password-change card.

## 7. Admin enforcement

- `Ensure2faEnrolled` (alias `2fa.enrolled`) reads the **session flag** `2fa.required` — NOT the DB per request (avoids per-request DB work; set at every real login, cleared on enrollment success).
- Flag set: on login (password or Google) when `role == admin` and `two_factor_confirmed_at` is null; on disable.
- Applied to the `admin` route group (same group that carries `auth`): redirects to `/profile/settings` with the 2FA card expanded + a notice. `/logout` and the settings page itself are exempt.
- `actingAs` test sessions never carry the flag → existing AdminProfileTest unaffected.

## 8. Google OAuth

- `services.google` config (client_id/secret/redirect from env). New env keys: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` (added to `.env` and `.env.example`).
- `GET /auth/google/redirect` — `Socialite::driver('google')->redirect()`.
- `GET /auth/google/callback`:
  1. `Socialite::driver('google')->user()` (stateless not needed — same domain).
  2. Match by email: existing user → link `google_id` if empty, update avatar, proceed.
  3. No match → create `role=user`, `status=active`, `password=null`, `google_id`, `avatar`, `name` from Google.
  4. `google_id` already linked to a DIFFERENT email → error page "linked to another account" (identity theft guard).
  5. If account `twoFactorEnabled()` → same challenge flow (pending + redirect). Else if admin without 2FA → `2fa.required` flag + settings. Else login + regenerate + intended.
- "Continue with Google" button on `/login` and `/signup` views (styled to the design system, no inline styles).
- Settings card: "Connect Google Account" section — if linked shows email + avatar (and "connected" state), else a link button (logged-in users landing on callback get linked, not created).

## 9. Security decisions

- OTP codes hashed at rest (cache), TOTP secret encrypted at rest, replay protection for TOTP via verified-timestamp cache.
- Session regenerated on every real login (password AND Google).
- `2fa.pending` stores only the user id; the real auth identity is established only after code verification.
- Throttles (TelemetryPipeline `RouteServiceProvider::RateLimiter::for`): `2fa` 5/min, `2fa-resend` 1/min, `2fa-enroll` 10/min — by IP; plus the in-session attempt caps.
- OTP send is queued (database queue; `queue:work` running on server; sync in tests via `Mail::fake`).
- Google-only accounts: `AuthController::login` returns "This account uses Google sign-in" when password is null.
- Audit trail: `Log::info('auth.2fa_enabled' | 'auth.2fa_disabled' | 'auth.2fa_challenge' | 'auth.google_linked' | ...)` with user id + method — consistent with the existing `\Log`-based auditing in AuthController.

## 10. Testing

New `Modules/IdentityAccess/tests/Feature/TwoFactorTest.php` + `GoogleAuthTest.php` (RefreshDatabase, module TestCase):
- Enrollment: TOTP enable → QR session pending → wrong code rejected → correct code confirms (`two_factor_confirmed_at` set); email enable → code from cache verified; disable clears and re-arms admin flag.
- Challenge: password login with 2FA → session pending → challenge page → wrong code (5× → invalidated, redirected to login) → correct code logs in + regenerates; resend cooldown 422/429 under 60s.
- Admin gate: unenrolled admin real-login sets flag → admin route redirects to settings → after confirm, flag cleared, route accessible; actingAs admin unaffected.
- Google: `Socialite::fake()` — auto-link existing email; auto-create new email (password null, google_id set); conflicting link error; 2FA-enabled Google user hits challenge.
- AuthController: google-only account password login error.
- Existing 48 tests stay green (all `actingAs`-based).

## 11. Deployment order

1. 2FA complete + green → ship (push → server pull → composer install → migrate).
2. Google: needs real Google Cloud OAuth credentials (user creates console project + adds `GOOGLE_CLIENT_ID/SECRET/REDIRECT` to server `.env`); until then buttons render but callback fails cleanly. Tests use Socialite fakes, so suite is green without credentials.

## 12. Out of scope (noted, not built)

Recovery codes; SMS; per-IP remember-2FA device trust; password reset for Google-only accounts; banning/account-recovery flows.