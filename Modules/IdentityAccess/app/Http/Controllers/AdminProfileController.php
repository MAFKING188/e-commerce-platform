<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\IdentityAccess\Services\GovernanceService;

class AdminProfileController extends Controller
{
    public function index(GovernanceService $governance)
    {
        $user = Auth::user()->load(['orders', 'addresses', 'reviews', 'wishlists']);

        $metrics = $governance->getDashboardMetrics();

        $stats = [
            'Revenue' => '$' . number_format($metrics['stats']['revenue'] ?? 0, 0),
            'Active orders' => $metrics['stats']['active_orders'] ?? 0,
            'Members' => \Modules\IdentityAccess\Models\User::count(),
            'Pending reviews' => $metrics['stats']['pending_reviews'] ?? 0,
        ];

        return view('identityaccess::admin.profile', [
            'user' => $user,
            'stats' => $stats,
            'timeline' => $user->activityTimeline(8),
        ]);
    }
}