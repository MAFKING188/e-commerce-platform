<?php

namespace Modules\EmailCenter\Http\Controllers;

use Illuminate\Http\Request;
use Modules\EmailCenter\Models\EmailLog;
use App\Http\Controllers\Controller;

class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailLog::with('sender')->latest();

        if ($request->filled('status') && in_array($request->status, ['pending', 'sent', 'failed'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(fn ($w) => $w->where('recipient_email', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%"));
        }

        $logs = $query->paginate(15)->withQueryString();

        return view('emailcenter::admin.email-logs', compact('logs'));
    }
}