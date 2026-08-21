<?php

namespace Modules\EmailCenter\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PartnerEmailSendController extends Controller
{
    public function compose() { return view('emailcenter::partner.email-compose'); }
    public function send(Request $request) { return redirect()->back(); }
}