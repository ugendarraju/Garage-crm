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

        return redirect()->route('dashboard')->with('success', 'You are now managing the selected garage.');
    }

    public function leave(Request $request)
    {
        session()->forget('active_garage_id');

        return redirect()->route('garages.index')->with('success', 'Returned to Super Admin dashboard.');
    }
}
