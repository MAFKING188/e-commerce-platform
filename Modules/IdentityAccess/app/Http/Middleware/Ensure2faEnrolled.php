<?php

namespace Modules\IdentityAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Ensure2faEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isAdmin() && session('2fa.required')) {
            return redirect()->route('profile.settings')
                ->with('status', 'Two-factor authentication is required for admin accounts. Please enable it to continue.');
        }

        return $next($request);
    }
}