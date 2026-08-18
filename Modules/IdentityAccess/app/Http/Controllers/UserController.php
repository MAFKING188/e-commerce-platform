<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\IdentityAccess\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
public function show()
{
    $user = Auth::user()->load(['orders', 'addresses', 'reviews', 'wishlists']);
    $address = $user->addresses->firstWhere('is_primary', true) ?? $user->addresses->first();

    $stats = [
        'Orders placed' => $user->orders->count(),
        'Total spent' => '$' . number_format($user->orders->where('status', 'completed')->sum('total_price'), 0),
        'Archived pieces' => $user->wishlists->count(),
    ];

    return view('identityaccess::users.show', [
        'user' => $user,
        'address' => $address,
        'stats' => $stats,
        'timeline' => $user->activityTimeline(8),
        'recentOrders' => $user->orders()->latest()->take(4)->get(),
    ]);
}

    /**
     * SELF-SERVICE: Update own profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,

            // Address validation
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
        ]);

        // Update user model
        $user->update($request->only(['name', 'email']));

        // Update existing primary address OR create one
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

        return redirect()->back()
            ->with('success', 'Identity and Residence updated successfully');
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
        ]);

        Auth::user()->update(['password' => $request->password]);
        $request->session()->regenerate();

        return redirect()->back()->with('success', 'Password updated successfully');
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
