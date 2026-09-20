<?php

namespace App\Http\Controllers;

use App\Models\VehicleModel;
use App\Models\VehicleBrand;
use App\Models\Garage;
use Illuminate\Http\Request;

class VehicleModelController extends Controller
{
    private function authorizeStaff(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index(Request $request, $brandId)
    {
        // Public/Authenticated API for fetching models under a brand
        $models = VehicleModel::where('brand_id', $brandId)->orderBy('name')->get();
        return response()->json($models);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff();

        $request->validate([
            'brand_id' => 'required|exists:vehicle_brands,id',
            'name' => 'required|string',
        ]);

        $garageId = Garage::getActiveGarageId();
        $user = auth()->user();
        if (!$garageId && $user->role !== 'super_admin') {
            abort(403, 'Must be logged in to a garage.');
        }

        $brand = VehicleBrand::findOrFail($request->brand_id);
        
        // Ensure they either own the brand or the brand is global, or they are super_admin
        if ($user->role !== 'super_admin' && $brand->garage_id !== null && (int) $brand->garage_id !== (int) $garageId) {
            abort(403, 'Cannot add a model to this brand.');
        }

        $modelNames = array_map('trim', explode(',', $request->name));
        $addedCount = 0;

        foreach ($modelNames as $modelName) {
            if (empty($modelName)) continue;

            $exists = VehicleModel::where('brand_id', $request->brand_id)
                ->where('name', 'LIKE', $modelName)
                ->first();

            if (!$exists) {
                VehicleModel::create([
                    'brand_id' => $request->brand_id,
                    'name' => $modelName,
                    'garage_id' => $user->role === 'super_admin' && !$garageId ? null : $garageId,
                ]);
                $addedCount++;
            }
        }

        return redirect()->back()->with('success', $addedCount . ' model(s) created successfully. Skipped duplicates if any.');
    }

    public function destroy(VehicleModel $model)
    {
        $this->authorizeStaff();

        $garageId = Garage::getActiveGarageId();
        $user = auth()->user();
        
        // If global model, only super_admin can delete
        if ($model->garage_id === null && $user->role !== 'super_admin') {
            abort(403, 'Only Super Admin can delete global vehicle models.');
        }

        // If belongs to a garage, user must belong to that garage or be super_admin
        if ($model->garage_id !== null && $user->role !== 'super_admin' && (int) $model->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $model->delete();
        return redirect()->back()->with('success', 'Model deleted successfully.');
    }
}
