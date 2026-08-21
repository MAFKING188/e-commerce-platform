<?php

namespace Modules\EmailCenter\Http\Controllers;

use App\Http\Controllers\Controller;

class EmailLogController extends Controller
{
    public function index() { return view('emailcenter::admin.email-logs'); }
}