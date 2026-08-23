<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Mail\UserStatusUpdated;
use Modules\TelemetryPipeline\Services\TelemetryService;
use Modules\PartnerHub\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminUserController extends Controller
{
    public function __construct(private TelemetryService $telemetry)
    {
    }

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
     * Edit a member's access credentials (role + status).
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('identityaccess::admin.users.edit', compact('user'));
    }

    /**
     * Update user role and status.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldStatus = $user->status;

        $request->validate([
            'role' => 'required|in:user,partner,admin',
            'status' => 'required|in:active,pending,suspended'
        ]);

        $user->update($request->only(['role', 'status']));

        // Keep partner registry in sync with role changes so the artisan portal never 404s
        if ($user->role === 'partner') {
            Partner::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $user->name, 'description' => null, 'contact_info' => $user->email, 'website' => null]
            );
        }

        $this->telemetry->log('admin.users.update', ['user_id' => $id]);

        if ($oldStatus !== $user->status) {
            Mail::to($user->email)->send(new UserStatusUpdated($user));
        }


        return redirect()->back()->with('success', 'Member credentials refined.');
    }

    /**
     * Rapid Approval: Move from pending to active.
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        // Approved partners get their registry entry so the portal is reachable immediately
        if ($user->role === 'partner') {
            Partner::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $user->name, 'description' => null, 'contact_info' => $user->email, 'website' => null]
            );
        }

        $this->telemetry->log('admin.users.approve', ['user_id' => $id]);

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

        try {
            $user = User::findOrFail($id);
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Cannot purge this member: they have orders or partner records. Suspend the account instead.');
        }
        $this->telemetry->log('admin.users.destroy', ['user_id' => $id]);
        return redirect()->back()->with('success', 'Member purged from registry.');
    }
}
