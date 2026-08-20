<?php

namespace Modules\IdentityAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user() && auth()->user()->role === 'admin' && auth()->user()->status === 'active') {
            return $next($request);
        }
        return redirect()->route('home')->with('error', 'Access denied');

        /*

        use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

public function handle(Request $request, Closure $next)
{
    if (!Auth::check() || Auth::user()->role !== 'admin') {
        abort(403);
    }

    return $next($request);
}*/
    }
}
