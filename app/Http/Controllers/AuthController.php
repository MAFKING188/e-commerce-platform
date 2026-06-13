<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMember;

class AuthController extends Controller
{
    /* REGISTER */
    public function register(Request $request)
    {
        \Log::info('Registration Request:', $request->all());
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:user,partner,admin'
        ]);

        $status = ($data['role'] === 'user') ? 'active' : 'pending';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Password is hashed in the model cast
            'role' => $data['role'],
            'status' => $status
        ]);
        \Log::info('User Created:', $user->toArray());

        // 📧 Trigger Welcome Email
        Mail::to($user)->queue(new WelcomeMember($user));

        if ($status === 'active') {
            Auth::login($user);
            return redirect('/')->with('status', 'Welcome to the Collection! Account created.');
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

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials'
        ]);
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
}