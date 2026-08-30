<?php

namespace App\Http\Controllers;

use App\Models\VehicleModel;
use Illuminate\Http\Request;

class VehicleModelController extends Controller
{
    public function index(Request $request, $brandId)
    {
        $models = VehicleModel::where('brand_id', $brandId)->orderBy('name')->get();
        return response()->json($models);
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:vehicle_brands,id',
            'name' => 'required|string',
        ]);

        $garageId = \App\Models\Garage::getActiveGarageId();
        $user = auth()->user();
        if (!$garageId && $user->role !== 'super_admin') {
            abort(403, 'Must be logged in to a garage.');
        }

        $brand = \App\Models\VehicleBrand::findOrFail($request->brand_id);
        
        // Ensure they either own the brand or the brand is global, or they are super_admin
        if ($user->role !== 'super_admin' && $brand->garage_id !== null && $brand->garage_id !== $garageId) {
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
        $garageId = \App\Models\Garage::getActiveGarageId();
        $user = auth()->user();
        
        // Allowed for all

        $model->delete();
        return redirect()->back()->with('success', 'Model deleted successfully.');
    }
}
