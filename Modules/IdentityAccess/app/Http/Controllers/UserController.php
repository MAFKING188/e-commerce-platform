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
    $user = Auth::user()->load(['orders', 'addresses']);
    $address = $user->addresses->firstWhere('is_primary', true) ?? $user->addresses->first();
    return view('identityaccess::users.show', compact('user', 'address'));
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
