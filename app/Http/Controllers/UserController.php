<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('users.index', compact('users'));
    }

   public function show()
{
    $user = Auth::user()->load(['orders', 'addresses']);
    return view('users.show', compact('user'));
}

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:100',
        'email' => 'required|email',

        // Address validation
        'line1' => 'required|string|max:255',
        'city' => 'required|string|max:100',
        'country' => 'required|string|max:100',
    ]);

    // Update user model
    $user->update(
        $request->only([
            'name',
            'email'
        ])
    );

    /**
     * 💡 BACKEND HEAVY LIFTING: MULTI-MODEL UPDATE
     * TODO: Update the user's primary address.
     * Hint: Use $user->addresses()->updateOrCreate([], $request->only(['line1', 'city', 'country']));
     */

    // Update existing primary address OR create one
    $user->addresses()->updateOrCreate(

        // Find condition
        [
            'is_primary' => true
        ],

        // Data to update/create
        [
            'line1' => $request->line1,
            'city' => $request->city,
            'country' => $request->country,
        ]
    );

    return redirect()->back()
        ->with(
            'success',
            'Identity and Residence updated successfully'
        );
}

    public function destroy($id)
    {
        User::destroy($id);

        return redirect()->back()
            ->with('success', 'User deleted');
    }
}
