<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GarageImpersonationController extends Controller
{
    public function impersonate(Request $request, $garageId)
    {
        // Ensure only SuperAdmin can do this
        if ($request->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        session(['active_garage_id' => $garageId]);

        $garage = \App\Models\Garage::find($garageId);
        \App\Services\ActionLogService::log(
            action: 'impersonated',
            module: 'garages',
            description: "Super Admin started managing workshop '" . ($garage ? $garage->name : "#{$garageId}") . "'",
            garageId: $garageId
        );

        return redirect()->route('dashboard')->with('success', 'You are now managing the selected garage.');
    }

    public function leave(Request $request)
    {
        $activeGarageId = session('active_garage_id');
        if ($activeGarageId) {
            $garage = \App\Models\Garage::find($activeGarageId);
            \App\Services\ActionLogService::log(
                action: 'left_impersonation',
                module: 'garages',
                description: "Super Admin exited impersonation session for garage '" . ($garage ? $garage->name : "#{$activeGarageId}") . "'",
                garageId: $activeGarageId
            );
        }

        session()->forget('active_garage_id');

        return redirect()->route('garages.index')->with('success', 'Returned to Super Admin dashboard.');
    }
}
