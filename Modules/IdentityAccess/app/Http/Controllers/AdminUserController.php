<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Mail\UserStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminUserController extends Controller
{
    /**
     * Display a listing of all members with filtering for status.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(15);
        return view('identityaccess::admin.users.index', compact('users'));
    }

    /**
     * Update user role and status.
     */
    public function update(Request $request, $id)
    {
        \Log::info('DEBUG: Raw Request Input:', $request->all());
        $user = User::findOrFail($id);
        $oldStatus = $user->status;

        $request->validate([
            'role' => 'required|in:user,partner,admin',
            'status' => 'required|in:active,pending,suspended'
        ]);

        $user->update($request->only(['role', 'status']));
        
        if ($oldStatus !== $user->status) {
            Mail::to($user->email)->send(new UserStatusUpdated($user));
        }

        \Log::info('User Updated:', $user->fresh()->toArray());

        return redirect()->back()->with('success', 'Member credentials refined.');
    }

    /**
     * Rapid Approval: Move from pending to active.
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        Mail::to($user->email)->send(new UserStatusUpdated($user));

        return redirect()->back()->with('success', 'Member access granted.');
    }

    /**
     * Terminate member access.
     */
    public function destroy($id)
    {
        if (auth()->id() == $id) {
            return back()->with('error', 'Suicide is not a solution (You cannot delete yourself).');
        }

        User::destroy($id);
        return redirect()->back()->with('success', 'Member purged from registry.');
    }
}
