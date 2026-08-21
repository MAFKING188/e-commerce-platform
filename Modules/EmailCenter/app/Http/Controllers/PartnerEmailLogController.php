<?php

namespace Modules\EmailCenter\Http\Controllers;

use Illuminate\Http\Request;
use Modules\EmailCenter\Models\EmailLog;
use App\Http\Controllers\Controller;

class PartnerEmailLogController extends Controller
{
    public function index()
    {
        $logs = EmailLog::with('sender')
            ->where('sender_user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('emailcenter::partner.email-logs', compact('logs'));
    }
}