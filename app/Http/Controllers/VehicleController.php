<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\Garage;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:vehicle_brands,id',
            'vehicle_model_id' => 'required|exists:vehicle_models,id',
            'registration_number' => 'required|string|max:20',
            'customer_id' => 'nullable|exists:customers,id'
        ]);

        $garageId = Garage::getActiveGarageId();

        if (auth()->user()->role === 'customer') {
            $customerProfile = auth()->user()->customerProfile;
            if (!$customerProfile) {
                abort(403, 'Customer profile not found');
            }
            $validated['garage_id'] = $customerProfile->garage_id;
            $customerProfile->vehicles()->create($validated);
        } else {
            $request->validate(['customer_id' => 'required|exists:customers,id']);
            $customer = Customer::findOrFail($request->customer_id);

            if (auth()->user()->role !== 'super_admin' && (int) $customer->garage_id !== (int) $garageId) {
                abort(403, 'Unauthorized customer access.');
            }

            $validated['garage_id'] = $customer->garage_id;
            $customer->vehicles()->create($validated);
        }

        return redirect()->back()->with('success', 'Vehicle added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $garageId = Garage::getActiveGarageId();

        if (auth()->user()->role === 'customer') {
            if ($vehicle->customer_id !== auth()->user()->customerProfile?->id) {
                abort(403, 'Unauthorized access.');
            }
        } else {
            if (auth()->user()->role !== 'super_admin') {
                if ($vehicle->customer && (int) $vehicle->customer->garage_id !== (int) $garageId) {
                    abort(403, 'Unauthorized access.');
                }
            }
        }

        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle deleted successfully.');
    }
}
