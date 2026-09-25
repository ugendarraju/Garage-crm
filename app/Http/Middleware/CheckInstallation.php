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

        // In production/staging, installer is strictly disabled to prevent remote takeover
        if (!app()->isLocal()) {
            if (!$isInstalled) {
                @file_put_contents(storage_path('installed'), json_encode([
                    'sealed_at' => now()->toIso8601String(),
                    'environment' => app()->environment(),
                ]));
                $isInstalled = true;
            }
            if ($request->is('install*')) {
                return redirect('/');
            }
        }

        // If lock file is missing but database already has users/garages, permanently seal it
        if (!$isInstalled) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('users') && \App\Models\User::count() > 0) {
                    @file_put_contents(storage_path('installed'), json_encode([
                        'sealed_at' => now()->toIso8601String(),
                        'auto_sealed' => true,
                    ]));
                    $isInstalled = true;
                }
            } catch (\Throwable $e) {
                // Database not yet configured or reachable
            }
        }

        // If not installed and trying to visit application pages, redirect to installer
        if (!$isInstalled) {
            if (!$request->is('install*') && !$request->is('up') && !$request->is('build*') && !$request->is('favicon*')) {
                return redirect()->route('install.index');
            }
        }

        // If already installed, block access to installer completely
        if ($isInstalled && $request->is('install*')) {
            return redirect('/');
        }

        return $next($request);
    }
}
