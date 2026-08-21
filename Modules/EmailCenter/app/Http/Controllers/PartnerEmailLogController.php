<?php

namespace Modules\EmailCenter\Http\Controllers;

use App\Http\Controllers\Controller;

class PartnerEmailLogController extends Controller
{
    public function index() { return view('emailcenter::partner.email-logs'); }
}