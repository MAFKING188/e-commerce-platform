# Email-OTP 2FA for all risk tiers — Design

Date: 2026-08-19

## Problem

The current 2FA design forces admin accounts to enroll a TOTP authenticator
app (QR code + secret) before the admin console opens. The owner finds this
unacceptable: no app setup, no QR — the verification code must be sent by
email. The verification code must be mandatory for all privileged actors
(admins + partners) and, per the owner, available/required in a graduated way
for buyers (signup verification + step-up at sensitive actions).

## Goals

1. Verification codes are delivered **only by email** (6 digits, 10-minute TTL,
   single-use, hashed at rest — the existing `OtpService`).
2. **Admins and partners**: a code is required at **every login**. No
   enrollment step, no settings redirect, no app. Password + email code = in.
3. **Buyers**: optional opt-in "Email Codes" at login; **mandatory** step-up
   code at checkout, password change, email change, and 2FA settings changes;
   **mandatory** email verification at signup.
4. TOTP machinery is removed entirely (secret column, QR, `Google2FA` usage).

## Current state (relevant)

- `User`: `two_factor_type` (enum `TwoFactorType`, values `totp|email`),
  `two_factor_secret`, `two_factor_confirmed_at`; `twoFactorEnabled()`,
  `twoFactorMethod()`, `isAdmin()`.
- `OtpService`: `issue()` (caches `Hash::make($code)`, TTL 600),
  `send()` (queues `OtpMail`), `check()` (verifies + consumes).
- `AuthController::login`: if `twoFactorEnabled()` → `2fa.pending` +
  `2fa.pending_method` + `OtpService::send` (email) → `/2fa/challenge`.
  Else logs in; sets `session('2fa.required')` for admins.
- `TwoFactorController`: challenge/verify/resend + TOTP enrollment
  (`enableTotp`, `qr`), email enrollment (`enableEmail`), `confirm`, `disable`.
  TOTP branches in `verify()`/`confirm()`.
- `Ensure2faEnrolled` middleware (`2fa.enrolled`) redirects admins with
  `session('2fa.required')` to `profile.settings`. Applied to all four admin
  route groups.
- `Ensure2faChallenge` (`2fa.pending`) guards the `/2fa/challenge` routes.
- Routes: `profile.settings.twofa.enable-totp`, `.enable-email`, `.qr`,
  `.confirm`, `.disable`; `/orders/store` (`throttle:checkout`);
  `profile.update` / `profile.password`; `/createaccount` (register).
- No `email_verified_at` column exists.
- Blade: `partials/twofa-card.blade.php` (TOTP QR UI + email enable),
  `auth/challenge.blade.php` (email + totp hints).
- Test files: `TwoFactorTest.php`, `RegistrationTest.php`, `ProfileTest.php`,
  `PartnerOnboardingTest.php`, checkout/order feature tests.

## Design

### 1. Schema migration

New migration `2026_08_19_xxxxxx_email_otp_2fa`:

- Add `users.email_verified_at` timestamp nullable (signup verification).
- Drop `users.two_factor_secret` (TOTP gone).
- Keep `two_factor_type` (only value `email`) and `two_factor_confirmed_at`
  as the buyer opt-in flag: `twoFactorEnabled() == true` means the buyer
  opted into email codes.

`TwoFactorType` enum keeps a single `email` case (drop `totp`).

### 2. Challenge-before-login (mandatory for admins + partners)

Login flow becomes: password check → if user is admin, partner, **or** has
2FA enabled → send email code, set `2fa.pending`, redirect to challenge.
`Auth::login` happens **only** after a valid code is submitted.

- `AuthController::login`: remove the `session('2fa.required')` special-case
  and the `twoFactorEnabled()`-only gate; gate on
  `$user->isAdmin() || $user->isPartner() || $user->twoFactorEnabled()`.
  Remove the `isAdmin()` flag from `verify()` and `disable()`.
- `TwoFactorController`: delete all TOTP paths (`verifyTotp`, `enableTotp`,
  `qr`, totp branches in `confirm`). `challenge()`/`verify()`/`resend()` use
  email-only; the pending user must be admin/partner or 2FA-enabled, else
  redirect to login.
- Delete `Ensure2faEnrolled` middleware and its alias; remove `2fa.enrolled`
  from all four admin route groups (it is dead: an admin/partner cannot hold a
  session without completing the challenge).
- Remove routes `profile.settings.twofa.enable-totp` and
  `profile.settings.twofa.qr`; keep `enable-email`, `confirm`, `disable`.

Consequences: sessions from before the deploy are invalid (everyone signs in
again once); the settings gate disappears; admin/partner first login sends the
code immediately.

### 3. Buyer opt-in toggle (settings)

`partials/twofa-card.blade.php` is simplified to a single flow:

- Disabled: one "Enable Email Codes" form (current password) → `enableEmail`
  sends the code, sets `twofa.pending_type=email` → confirm form (code) →
  `confirm` sets `two_factor_type='email'` + `two_factor_confirmed_at`.
- Enabled: status line "Enabled via Email Codes since …" + disable form
  (current password **and** a fresh code; invalid code sends a new one).

No QR, no app references anywhere.

### 4. Buyer step-up (checkout, password change, email change)

New small helper `Modules\IdentityAccess\Services\StepUpService` wrapping
`OtpService` + session:

- `begin(User $user)`: send OTP, set `session('stepup.pending') = true`.
- `isVerified($request->session())`: true if `session('stepup.verified')` is a
  timestamp less than 15 minutes old (TTL 900).
- `complete($request->session())`: set the verified timestamp.
- `invalidate($request->session())`: clear both keys.

Checkout — `OrderController::store`:

- If `StepUpService::isVerified` → place order (current behavior).
- Else if request has `code`: `OtpService::check` → valid: `complete` + place
  order; invalid: `begin` (new code) + back with error on the `code` field.
- Else: `begin` + back with error "Enter the verification code sent to your
  email" (the checkout blade shows the code field when
  `session('stepup.pending')` or a `code` error is present; address fields
  preserved via `old()`).

Password change (`UserController::updatePassword`) and email change
(`UserController::updateProfile`):

- Email change: the code is required **only when the submitted email differs
  from the user's current email**. Address-only submissions (the BUG 2 flow)
  never require a code.
- The request must include `code`. Missing or invalid → `OtpService::send` +
  back with a `code` error; the change is **not** applied. Valid → apply.

2FA settings changes (`disable`): requires current password **and** a fresh
code; invalid code sends a new one and does not disable.

### 5. Signup email verification (bundled)

- `AuthController::register`: create the user (email unverified), auto-login
  (as today), send the verification code (`OtpService::send`), set
  `email.verify.pending = user id`, redirect to `/verify-email`.
- New routes (names are new; existing route names untouched):
  - `GET  /verify-email` → verify page (form with code).
  - `POST /verify-email` (throttle `2fa-verify`) → check code; valid:
    `email_verified_at = now`, clear pending, redirect home; invalid: new code
    sent + error.
  - `POST /verify-email/resend` (throttle `2fa-resend`) → resend code.
- New blade `auth/verify-email.blade.php`.
- `AuthController::login`: if the account exists and `email_verified_at` is
  null → set the pending flag, send the code, redirect to `/verify-email`
  (generic success message; no account-enumeration difference).
- Welcome mail unchanged (still sent at signup, as today).

### 6. Error handling & rate limits

- All OTP sends use generic messages ("If that email exists / a code is on its
  way") to avoid account enumeration.
- Reuse/extend existing throttles: `2fa`, `2fa-resend`, `2fa-enroll`,
  `2fa-verify`, `checkout`, `auth`.
- Attempts: 5 invalid codes on a challenge invalidates the session (existing
  behavior, kept).

### 7. Tests (TDD, one task = one commit)

- `TwoFactorTest` rewrite: mandatory challenge for admin and partner at login
  (no enrollment gate), buyer opt-in enable/confirm/disable (with code),
  challenge verify/resend/attempt throttle, email-only (no totp).
- New `StepUpTest`: checkout requires a code once; valid code places the order;
  verified marker bypasses repeat codes within TTL; expired marker re-requires.
- Password/email change tests: code required, invalid code does not apply,
  valid applies.
- `RegistrationTest`: signup → verify page → code → verified; unverified login
  redirects to verify page; resend throttle.
- Full suite green (current baseline 86 tests / 335 assertions).

### 8. Rollout

- Commit per task, TDD, conventional commits, explicit `git add` (no `git add .`).
- Deploy to production: push, pull, migrate, queue restart. Verify live:
  admin login → code by email → challenge → console; partner login same;
  buyer signup verification; checkout step-up.

## Out of scope

- Google OAuth (untouched; still optional).
- PayPal capture (external sandbox credentials).
- `OrderConfirmed`/order-status emails (separate P1 wiring task).