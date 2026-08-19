<?php

namespace Modules\IdentityAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Ensure2faChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            session()->forget('2fa.pending');
            return redirect('/');
        }

        if (! session('2fa.pending')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}