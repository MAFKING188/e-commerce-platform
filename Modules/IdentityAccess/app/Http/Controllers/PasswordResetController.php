<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\IdentityAccess\Mail\PasswordChangedMail;
use Modules\IdentityAccess\Mail\PasswordResetMail;

class PasswordResetController extends Controller
{
    public function showForgotForm(): View
    {
        return view('identityaccess::auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email'),
            function ($user, string $token) {
                Mail::to($user)->queue(new PasswordResetMail($user, $token));
            }
        );

        \Log::debug('RESET_STATUS: ' . $status);

        return back()->with('status', 'If that email exists, a reset link is on its way.');
    }

    public function showResetForm(string $token): View
    {
        return view('identityaccess::auth.reset-password', ['token' => $token]);
    }

    public function storeNewPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
                $user->setRememberToken(Str::random(60));
                Mail::to($user)->queue(new PasswordChangedMail($user));
                Auth::login($user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('/')->with('status', 'Password reset successfully. Welcome back!')
            : back()->withErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
    }
}