<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;

class TwoFactorController extends Controller
{
    private const MAX_CONFIRM_ATTEMPTS = 5;
    private const MAX_CHALLENGE_ATTEMPTS = 5;

    /* ---------- challenge ---------- */

    public function challenge()
    {
        $userId = session('2fa.pending');
        $user = User::find($userId);

        if (! $user || (! $user->twoFactorEnabled() && ! $user->isAdmin() && ! $user->isPartner())) {
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

        if (! $user || (! $user->twoFactorEnabled() && ! $user->isAdmin() && ! $user->isPartner())) {
            session()->forget('2fa.pending');
            return redirect()->route('login');
        }

        $valid = OtpService::check($user, trim($data['code']));

        if (! $valid) {
            $attempts = (int) session('2fa.attempts', 0) + 1;
            session(['2fa.attempts' => $attempts]);
            if ($attempts >= self::MAX_CHALLENGE_ATTEMPTS) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors(['code' => 'Too many invalid attempts. Please sign in again.']);
            }
            return back()->withErrors(['code' => 'The code is invalid or has expired.']);
        }

        $request->session()->forget(['2fa.pending', '2fa.attempts', '2fa.pending_method', '2fa:otp:' . $user->id]);
        Auth::login($user);
        $request->session()->regenerate();
        Log::info('auth.2fa_challenge', ['user' => $user->id, 'method' => 'email']);

        return redirect()->intended('/');
    }

    public function resend(Request $request)
    {
        $userId = session('2fa.pending');
        $user = User::find($userId);

        if (! $user || (! $user->twoFactorEnabled() && ! $user->isAdmin() && ! $user->isPartner())) {
            session()->forget('2fa.pending');
            return redirect()->route('login');
        }

        if (Cache::has('2fa:resend:' . $user->id)) {
            return back()->withErrors(['code' => 'Please wait a moment before requesting another code.']);
        }

        OtpService::send($user);
        Cache::put('2fa:resend:' . $user->id, true, 60);

        return back()->with('status', 'A new verification code was sent to your email.');
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

        OtpService::send($user);
        session(['twofa.pending_type' => 'email']);

        return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
    }

    /* ---------- confirmation ---------- */

    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:10']);
        $user = $request->user();
        $pending = session('twofa.pending_type');

        if (! $pending) {
            return back()->withErrors(['code' => 'No pending 2FA setup. Start again.']);
        }

        $valid = OtpService::check($user, trim($data['code']));

        if (! $valid) {
            $attempts = (int) session('twofa.confirm_attempts', 0) + 1;
            session(['twofa.confirm_attempts' => $attempts]);
            if ($attempts >= self::MAX_CONFIRM_ATTEMPTS) {
                session()->forget(['twofa.pending_type', 'twofa.confirm_attempts']);
                return back()->withErrors(['code' => 'Too many invalid attempts. 2FA setup was reset — start again.']);
            }
            return back()->withErrors(['code' => 'The code is invalid or has expired.']);
        }

        $user->forceFill([
            'two_factor_type' => $pending,
            'two_factor_confirmed_at' => now(),
        ])->save();
        session()->forget(['twofa.pending_type', 'twofa.confirm_attempts']);
        Log::info('auth.2fa_enabled', ['user' => $user->id, 'method' => $pending]);

        return back()->with('status', 'Two-factor authentication is now active.');
    }

    public function sendDisableCode(Request $request)
    {
        OtpService::send($request->user());
        return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
    }

    /* ---------- disable ---------- */

    public function disable(Request $request)
    {
        $data = $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|max:10',
        ]);
        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Current password is incorrect.']);
        }

        if (! OtpService::check($user, trim($data['code']))) {
            OtpService::send($user);
            return back()->withErrors(['code' => 'The code is invalid or has expired. A new code was sent to your email.']);
        }

        $user->forceFill([
            'two_factor_type' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        Log::info('auth.2fa_disabled', ['user' => $user->id]);

        return back()->with('status', 'Two-factor authentication disabled.');
    }
}