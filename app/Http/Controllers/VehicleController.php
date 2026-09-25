<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\Garage;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $garageId = Garage::getActiveGarageId();
        $isCustomer = auth()->user()->role === 'customer';

        $query = Vehicle::with(['brand', 'vehicleModel', 'customer.user'])
            ->withCount('jobCards');

        if ($isCustomer) {
            $customerProfile = auth()->user()->customerProfile;
            if ($customerProfile) {
                $query->where('customer_id', $customerProfile->id);
            } else {
                $query->where('id', 0);
            }
        } else {
            $query->where(function ($q) use ($garageId) {
                $q->where('garage_id', $garageId)
                  ->orWhereHas('customer', function ($cq) use ($garageId) {
                      $cq->where('garage_id', $garageId);
                  })
                  ->orWhereHas('jobCards', function ($jq) use ($garageId) {
                      $jq->where('garage_id', $garageId);
                  });
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vehicleModel', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer.user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $vehicles = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'search' => $search ?? '',
        ]);
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
        $garageId = Garage::getActiveGarageId();
        $isCustomer = auth()->user()->role === 'customer';

        if ($isCustomer) {
            if ($vehicle->customer_id !== auth()->user()->customerProfile?->id) {
                abort(403, 'Unauthorized access.');
            }
        }

        $vehicle->load([
            'brand',
            'vehicleModel',
            'customer.user',
            'jobCards' => function ($q) {
                $q->orderBy('created_at', 'desc')->with(['mechanics', 'items', 'issues']);
            },
        ]);

        // Calculate vehicle timeline stats
        $totalVisits = $vehicle->jobCards->count();
        $lifetimeSpend = $vehicle->jobCards->sum('final_total');
        $latestKm = $vehicle->jobCards->whereNotNull('vehicle_running_km')->first()?->vehicle_running_km;
        $nextReminder = $vehicle->jobCards->whereNotNull('next_service_reminder')->sortByDesc('next_service_reminder')->first()?->next_service_reminder;

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'stats' => [
                'total_visits' => $totalVisits,
                'lifetime_spend' => (float) $lifetimeSpend,
                'latest_km' => $latestKm,
                'next_reminder' => $nextReminder,
            ],
        ]);
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
