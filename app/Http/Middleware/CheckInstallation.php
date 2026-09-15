<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = file_exists(storage_path('installed'));

        // If not installed and trying to visit application pages, redirect to installer
        if (!$isInstalled) {
            if (!$request->is('install*') && !$request->is('up') && !$request->is('build*') && !$request->is('favicon*')) {
                return redirect()->route('install.index');
            }
        }

        // If already installed, block access to installer
        if ($isInstalled && $request->is('install*')) {
            return redirect('/');
        }

        return $next($request);
    }
}
