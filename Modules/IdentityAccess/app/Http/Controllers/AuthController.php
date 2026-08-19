<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\IdentityAccess\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Mail\WelcomeMember;
use Modules\PartnerHub\Models\Partner;

class AuthController extends Controller
{
    /* REGISTER */
    public function register(Request $request)
    {
        \Log::info('Registration Request:', $request->all());
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:user,partner,admin',
            'phone' => 'required|string|max:30',
            'country' => 'required|string|size:2',
            'newsletter_optin' => 'sometimes|boolean'
        ]);

        $status = ($data['role'] === 'user') ? 'active' : 'pending';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Password is hashed in the model cast
            'role' => $data['role'],
            'status' => $status,
            'phone' => $data['phone'],
            'country' => $data['country'],
            'newsletter_optin' => $data['newsletter_optin'] ?? false
        ]);
        \Log::info('User Created:', $user->toArray());

        $countryCurrency = [
            'MA' => 'MAD', 'US' => 'USD', 'GB' => 'GBP',
            'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR', 'DE' => 'EUR',
            'BE' => 'EUR', 'PT' => 'EUR', 'NL' => 'EUR',
        ];
        session(['currency' => $countryCurrency[$data['country']] ?? config('currency.default')]);

        // Partner artisan: scaffold their partner registry entry so the portal is immediately usable after approval
        if ($data['role'] === 'partner') {
            Partner::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $data['name'], 'description' => null, 'contact_info' => $data['email'], 'website' => null]
            );
        }

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
    }

    /* LOGIN */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Check if user is active before attempting login
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->status !== 'active') {
            return back()->withErrors([
                'email' => 'Your account is currently ' . $user->status . '. Please contact support.'
            ]);
        }

        if (! $user || $user->password === null) {
            return back()->withErrors([
                'email' => $user ? 'This account uses Google sign-in.' : 'Invalid credentials',
            ]);
        }

        if (! Auth::validate($credentials)) {
            return back()->withErrors([
                'email' => 'Invalid credentials'
            ]);
        }

        if ($user->email_verified_at === null) {
            \Modules\IdentityAccess\Services\OtpService::send($user);
            session(['email.verify.pending' => $user->id]);
            return redirect()->route('verify-email')->with('status', 'A verification code was sent to your email.');
        }

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
    }

    /* API REGISTER */
    public function apiRegister(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'user'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /* API LOGIN */
    public function apiLogin(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /* LOGOUT */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

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
}