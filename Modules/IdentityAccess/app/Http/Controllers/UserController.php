<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\IdentityAccess\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10);
        return view('identityaccess::users.index', compact('users'));
    }

   public function show()
{
    $user = Auth::user()->load(['orders', 'addresses']);
    return view('identityaccess::users.show', compact('user'));
}

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('identityaccess::users.edit', compact('user'));
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
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
        ]);

        // Update user model
        $user->update($request->only(['name', 'email']));

        // Update existing primary address OR create one
        $user->addresses()->updateOrCreate(
            ['is_primary' => true],
            [
                'line1' => $request->line1,
                'city' => $request->city,
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
