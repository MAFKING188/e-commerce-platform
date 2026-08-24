<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;

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

            // Google has proven email ownership — mark verified. forceFill
            // because email_verified_at is deliberately not mass-assignable.
            $user->forceFill(['email_verified_at' => now()])->save();

            // Welcome parity with password signups.
            Mail::to($user)->queue(new \Modules\IdentityAccess\Mail\WelcomeMember($user));
        }

        if ($user->status !== 'active') {
            Log::warning('auth.google_status_blocked', ['user' => $user->id, 'status' => $user->status]);
            return redirect()->route('login')->withErrors(['email' => 'Your account is currently ' . $user->status . '. Please contact support.']);
        }

        Log::info('auth.google_linked', ['user' => $user->id]);

        if ($user->isAdmin() || $user->isPartner() || $user->twoFactorEnabled()) {
            session([
                '2fa.pending' => $user->id,
                '2fa.attempts' => 0,
                '2fa.pending_method' => 'email',
            ]);
            OtpService::send($user);
            return redirect()->route('2fa.challenge');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}