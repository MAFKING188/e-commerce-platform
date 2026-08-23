<?php

namespace Modules\TelemetryPipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TelemetryPipeline\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('actor')->latest();

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where('action', 'like', "%{$q}%");
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', (int) $request->actor_id);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('telemetrypipeline::admin.audit-logs.index', [
            'logs' => $logs,
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'actors' => \Modules\IdentityAccess\Models\User::whereIn('id', AuditLog::select('actor_id')->distinct()->pluck('actor_id'))->orderBy('name')->get(['id', 'name']),
        ]);
    }
}