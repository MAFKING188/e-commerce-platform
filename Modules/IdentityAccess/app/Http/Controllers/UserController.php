<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Mail\PasswordChangedMail;
use Modules\IdentityAccess\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
public function show()
{
    $user = Auth::user()->load(['orders', 'addresses', 'reviews', 'wishlists']);

    $stats = [
        'Orders placed' => $user->orders->count(),
        'Total spent' => '$' . number_format($user->orders->where('status', 'completed')->sum('total_price'), 0),
        'Archived pieces' => $user->wishlists->count(),
    ];

    return view('identityaccess::users.show', [
        'user' => $user,
        'stats' => $stats,
        'timeline' => $user->activityTimeline(8),
    ]);
}

    /**
     * SELF-SERVICE: Update own profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',

            // Address validation (optional — set on the Address & Security page)
            'line1' => 'nullable|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        // Email changes require a fresh verification code (step-up)
        if ($request->filled('email') && strtolower($request->email) !== strtolower($user->email)) {
            $request->validate(['code' => 'required|string|max:10']);
            if (! \Modules\IdentityAccess\Services\OtpService::check($user, trim($request->code))) {
                \Modules\IdentityAccess\Services\OtpService::send($user);
                return back()->withErrors(['code' => 'The code is invalid or has expired. A new code was sent to your email.']);
            }
        }

        // Update user model
        $user->update($request->only(['name', 'email', 'phone']));

        // Update existing primary address OR create one (when address fields are submitted)
        if ($request->filled('line1') || $request->filled('city') || $request->filled('country')) {
            $user->addresses()->updateOrCreate(
                ['is_primary' => true],
                [
                    'line1' => $request->line1,
                    'line2' => $request->line2,
                    'city' => $request->city,
                    'state' => $request->state,
                    'zip' => $request->zip,
                    'country' => $request->country,
                ]
            );
        }

        return redirect()->back()
            ->with('success', 'Identity and Residence updated successfully');
    }

    public function security()
    {
        $user = Auth::user()->load('addresses');
        $address = $user->addresses->firstWhere('is_primary', true) ?? $user->addresses->first();

        return view('identityaccess::users.security', compact('user', 'address'));
    }

    public function settings()
    {
        return view('identityaccess::users.settings', ['user' => Auth::user()]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $user = Auth::user();

        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatars && \Illuminate\Support\Facades\Storage::disk('public')->exists('avatars/' . $user->avatars)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete('avatars/' . $user->avatars);
        }

        $user->update(['avatars' => basename($path)]);

        return redirect()->back()->with('success', 'Profile photo updated');
    }

    public function updatePassword(Request $request)
    {
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

        Auth::user()->update(['password' => $request->password]);
        Mail::to(Auth::user())->queue(new PasswordChangedMail(Auth::user()));
        $request->session()->regenerate();

        return redirect()->back()->with('success', 'Password updated successfully');
    }

    public function sendEmailCode()
    {
        \Modules\IdentityAccess\Services\OtpService::send(Auth::user());
        return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
    }

    public function sendPasswordCode()
    {
        \Modules\IdentityAccess\Services\OtpService::send(Auth::user());
        return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
    }

    /**
     * ADMINISTRATIVE: Update any user (Role, Name, Email)
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:user,admin',
        ]);

        $user->update($request->only(['name', 'email', 'role']));

        return redirect()->route('users.index')
            ->with('success', 'Member permissions updated successfully');
    }

    public function destroy($id)
    {
        User::destroy($id);

        return redirect()->back()
            ->with('success', 'User deleted');
    }
}
