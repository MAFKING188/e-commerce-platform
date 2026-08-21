<?php

namespace Modules\EmailCenter\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EmailSendController extends Controller
{
    public function compose() { return view('emailcenter::admin.email-compose'); }
    public function send(Request $request) { return redirect()->back(); }
    public function searchUsers(Request $request) { return response()->json([]); }
}