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
use PragmaRX\Google2FALaravel\Facade as Google2FA;
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