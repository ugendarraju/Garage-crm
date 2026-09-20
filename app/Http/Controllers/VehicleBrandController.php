<?php

namespace App\Http\Controllers;

use App\Models\VehicleBrand;
use App\Models\Garage;
use Illuminate\Http\Request;

class VehicleBrandController extends Controller
{
    private function authorizeStaff(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index()
    {
        $this->authorizeStaff();
        $brands = VehicleBrand::with('models')->orderBy('name')->get();
        return \Inertia\Inertia::render('VehicleDatabase/Index', [
            'brands' => $brands
        ]);
    }

    public function apiIndex(Request $request)
    {
        // Get global brands (garage_id = null) + garage specific brands for vehicle selectors
        $brands = VehicleBrand::orderBy('name')->get();
        return response()->json($brands);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff();

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $garageId = Garage::getActiveGarageId();
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
        $this->authorizeStaff();

        $garageId = Garage::getActiveGarageId();
        $user = auth()->user();
        
        // If it's a global brand (garage_id === null), only super_admin can delete
        if ($brand->garage_id === null && $user->role !== 'super_admin') {
            abort(403, 'Only Super Admin can delete global vehicle brands.');
        }

        // If it belongs to a garage, user must belong to that garage or be super_admin
        if ($brand->garage_id !== null && $user->role !== 'super_admin' && (int) $brand->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $brand->delete();
        return redirect()->back()->with('success', 'Brand deleted successfully.');
    }
}
