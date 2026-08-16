<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\IdentityAccess\Services\GovernanceService;

class AdminDashboardController extends Controller
{
    /**
     * Display the Administrative Command Center.
     * Centralizes statistics and recent activity for platform management.
     */
    public function index(GovernanceService $governance)
    {
        $metrics = $governance->getDashboardMetrics();

        return view('identityaccess::admin.dashboard', $metrics);
    }
}
