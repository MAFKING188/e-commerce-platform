<?php

namespace Modules\PartnerHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PartnerHub\Models\Partner;
use Modules\TelemetryPipeline\Services\AnalyticsService;

class PartnerDashboardController extends Controller
{
    /**
     * Display the partner-specific dashboard.
     */
    public function index(AnalyticsService $analytics)
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();

        return view('partnerhub::partner.dashboard', ['partner' => $partner] + $analytics->partnerDashboard($partner));
    }
}
