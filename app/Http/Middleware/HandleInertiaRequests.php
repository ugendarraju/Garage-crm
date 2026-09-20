<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (!file_exists(storage_path('installed')) || $request->is('install*')) {
            return parent::share($request);
        }

        try {
            $activeGarageId = \App\Models\Garage::getActiveGarageId();
            $garage = $activeGarageId ? \App\Models\Garage::find($activeGarageId) : null;
        } catch (\Throwable $e) {
            $activeGarageId = null;
            $garage = null;
        }
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'active_garage_id' => $activeGarageId,
                'active_garage_name' => $garage ? $garage->name : null,
                'active_garage' => $garage,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
