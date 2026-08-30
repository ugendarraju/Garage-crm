<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $garageId = \App\Models\Garage::getActiveGarageId();
        
        $customers = Customer::with(['user', 'vehicles', 'jobCards.vehicle.brand', 'jobCards.vehicle.vehicleModel'])->withCount('jobCards')->latest()->get();
        
        $brands = \App\Models\VehicleBrand::where(function($query) use ($garageId) {
            $query->where('garage_id', $garageId)->orWhereNull('garage_id');
        })->get();
        
        $models = \App\Models\VehicleModel::where(function($query) use ($garageId) {
            $query->where('garage_id', $garageId)->orWhereNull('garage_id');
        })->with('brand')->get();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'brands' => $brands,
            'models' => $models,
        ]);
    }

    public function store(Request $request)
    {
        $existingUser = User::where('phone', $request->phone)->first();
        $userId = $existingUser ? $existingUser->id : null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20' . ($userId ? '' : '|unique:users,phone'),
            'email' => 'nullable|email|max:255|unique:users,email' . ($userId ? ',' . $userId : ''),
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:20',
            
            'vehicles' => 'nullable|array',
            'vehicles.*.registration_number' => 'required|string|max:20',
            'vehicles.*.brand_id' => 'required|exists:vehicle_brands,id',
            'vehicles.*.model_id' => 'required|exists:vehicle_models,id',
        ]);

        $user = User::firstOrCreate(
            ['phone' => $validated['phone']],
            [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? 'customer_' . $validated['phone'] . '@example.com',
                'role' => 'customer',
                'password' => bcrypt(Str::random(12))
            ]
        );

        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            [
                'address' => $validated['address'],
                'gst_number' => $validated['gst_number'],
                'whatsapp_number' => $validated['whatsapp_number'] ?? $validated['phone'],
            ]
        );
        
        if (!empty($validated['vehicles'])) {
            foreach ($validated['vehicles'] as $vehicleData) {
                \App\Models\Vehicle::create([
                    'customer_id' => $customer->id,
                    'registration_number' => $vehicleData['registration_number'],
                    'brand_id' => $vehicleData['brand_id'],
                    'model_id' => $vehicleData['model_id'],
                    'vehicle_type' => 'Car',
                    'fuel_type' => 'Petrol',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Customer added successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $userId = $customer->user_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . $userId,
            'email' => 'nullable|email|max:255|unique:users,email,' . $userId,
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:20',
            
            'vehicles' => 'nullable|array',
            'vehicles.*.id' => 'nullable|exists:vehicles,id',
            'vehicles.*.registration_number' => 'required|string|max:20',
            'vehicles.*.brand_id' => 'required|exists:vehicle_brands,id',
            'vehicles.*.model_id' => 'required|exists:vehicle_models,id',
        ]);

        $customer->user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? $customer->user->email,
        ]);

        $customer->update([
            'address' => $validated['address'],
            'gst_number' => $validated['gst_number'],
            'whatsapp_number' => $validated['whatsapp_number'],
        ]);
        
        $keptVehicleIds = [];
        if (!empty($validated['vehicles'])) {
            foreach ($validated['vehicles'] as $vehicleData) {
                if (!empty($vehicleData['id'])) {
                    // Update existing
                    $vehicle = \App\Models\Vehicle::where('id', $vehicleData['id'])->where('customer_id', $customer->id)->first();
                    if ($vehicle) {
                        $vehicle->update([
                            'registration_number' => $vehicleData['registration_number'],
                            'brand_id' => $vehicleData['brand_id'],
                            'model_id' => $vehicleData['model_id'],
                        ]);
                        $keptVehicleIds[] = $vehicle->id;
                    }
                } else {
                    // Create new
                    $vehicle = \App\Models\Vehicle::create([
                        'customer_id' => $customer->id,
                        'registration_number' => $vehicleData['registration_number'],
                        'brand_id' => $vehicleData['brand_id'],
                        'model_id' => $vehicleData['model_id'],
                        'vehicle_type' => 'Car',
                        'fuel_type' => 'Petrol',
                    ]);
                    $keptVehicleIds[] = $vehicle->id;
                }
            }
        }
        
        // Delete removed vehicles
        \App\Models\Vehicle::where('customer_id', $customer->id)
            ->whereNotIn('id', $keptVehicleIds)
            ->delete();

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $userId = $customer->user_id;
        $customer->delete();
        User::find($userId)?->delete();
        
        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }

    public function resetPassword(Customer $customer)
    {
        if ($customer->garage_id !== \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $customer->user->update([
            'password' => bcrypt('password')
        ]);

        return redirect()->back()->with('success', 'Customer password reset to "password".');
    }
}
