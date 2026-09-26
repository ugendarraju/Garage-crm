<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     * If user must change their temporary password, restrict access until updated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Allow force change routes, logout, and essential asset requests
            if (!$request->routeIs('password.force_change', 'password.force_update', 'logout')) {
                return redirect()->route('password.force_change');
            }
        }

        return $next($request);
    }
}
