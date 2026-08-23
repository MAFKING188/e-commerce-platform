<?php

namespace Modules\TelemetryPipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TelemetryPipeline\Models\EmailLog;

class OutboundMailController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailLog::query()->latest();

        if ($request->filled('status') && in_array($request->status, ['sent', 'failed'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(fn ($w) => $w->where('recipient', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%"));
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('telemetrypipeline::admin.outbound-mail.index', compact('logs'));
    }
}