<?php

namespace App\Http\Controllers;

use App\Models\VehicleBrand;
use Illuminate\Http\Request;

class VehicleBrandController extends Controller
{
    public function index()
    {
        $brands = VehicleBrand::with('models')->orderBy('name')->get();
        return \Inertia\Inertia::render('VehicleDatabase/Index', [
            'brands' => $brands
        ]);
    }

    public function apiIndex(Request $request)
    {
        // Get global brands (garage_id = null) + garage specific brands
        $brands = VehicleBrand::orderBy('name')->get();
        return response()->json($brands);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $garageId = \App\Models\Garage::getActiveGarageId();
        $user = auth()->user();
        if (!$garageId && $user->role !== 'super_admin') {
            abort(403, 'Must be logged in to a garage.');
        }

        VehicleBrand::create([
            'name' => $request->name,
            'garage_id' => $user->role === 'super_admin' && !$garageId ? null : $garageId,
        ]);

        return redirect()->back()->with('success', 'Brand created successfully.');
    }

    public function destroy(VehicleBrand $brand)
    {
        $garageId = \App\Models\Garage::getActiveGarageId();
        $user = auth()->user();
        
        // Allowed for all

        $brand->delete();
        return redirect()->back()->with('success', 'Brand deleted successfully.');
    }
}
