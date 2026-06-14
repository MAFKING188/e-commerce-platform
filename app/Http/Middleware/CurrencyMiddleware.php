<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurrencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('currency')) {
            $currency = strtoupper($request->currency);
            if (array_key_exists($currency, config('currency.supported'))) {
                session(['currency' => $currency]);
            }
        }

        if (!session()->has('currency')) {
            session(['currency' => config('currency.default')]);
        }

        return $next($request);
    }
}
