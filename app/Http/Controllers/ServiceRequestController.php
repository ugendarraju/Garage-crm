<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceRequestController extends Controller
{
    // Public: Show the booking form
    public function create(Request $request, $slug = null)
    {
        $brands = \App\Models\VehicleBrand::all();
        $models = \App\Models\VehicleModel::all();
        $garages = \App\Models\Garage::where('is_active', true)->get(['id', 'name', 'slug']);

        $identifier = $slug ?? $request->query('garage') ?? $request->query('garage_id');
        $selectedGarage = null;
        if ($identifier) {
            $selectedGarage = \App\Models\Garage::where('is_active', true)
                ->where(function ($q) use ($identifier) {
                    $q->where('slug', $identifier);
                    if (is_numeric($identifier)) {
                        $q->orWhere('id', (int) $identifier);
                    }
                })->first(['id', 'name', 'slug', 'tagline', 'address', 'phone', 'logo_path']);
        }

        $user = auth()->user();
        $isCustomer = $user && $user->role === 'customer';
        $customerVehicles = [];
        
        if ($isCustomer && $user->customerProfile) {
            $customerVehicles = $user->customerProfile->vehicles()->with(['brand', 'vehicleModel'])->get();
        }

        return Inertia::render('ServiceRequests/Create', [
            'brands' => $brands,
            'models' => $models,
            'garages' => $garages,
            'selectedGarage' => $selectedGarage,
            'isCustomer' => $isCustomer,
            'customerVehicles' => $customerVehicles,
            'customerDetails' => $isCustomer ? [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ] : null,
        ]);
    }

    // Public: Submit the booking form
    public function store(Request $request)
    {
        $validated = $request->validate([
            'garage_id' => 'required|exists:garages,id',
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'vehicle_brand' => 'required|string|max:255',
            'vehicle_model' => 'required|string|max:255',
            'vehicle_year' => 'required',
            'vehicle_running_km' => 'nullable|string',
            'fuel_range' => 'nullable|string',
            'issues' => 'required|array|min:1',
            'issues.*.description' => 'required|string',
            'issues.*.image' => 'nullable|image|max:15360',
        ]);

        // Check if existing user
        if (!auth()->check() && \App\Models\User::where('email', $validated['email'])->exists()) {
            return back()->withErrors(['email' => 'An account with this email already exists. Please log in to book a service.']);
        }

        $issuesData = [];
        if (is_array($request->issues)) {
            foreach ($request->issues as $index => $issueData) {
                $imagePath = null;
                if ($request->hasFile("issues.{$index}.image")) {
                    $imagePath = $request->file("issues.{$index}.image")->store('issues', 'public');
                }
                $issuesData[] = [
                    'description' => $issueData['description'],
                    'image_path' => $imagePath
                ];
            }
        }
        $validated['issues'] = json_encode($issuesData);

        // Create Service Request
        ServiceRequest::create($validated);

        return redirect()->back()->with('success', 'Your service request has been submitted successfully! We will contact you soon.');
    }

    private function authorizeStaff(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    // Admin: List all requests
    public function index()
    {
        $this->authorizeStaff();
        $garageId = \App\Models\Garage::getActiveGarageId();

        $requests = ServiceRequest::where('garage_id', $garageId)->orderBy('created_at', 'desc')->get();
        return Inertia::render('ServiceRequests/Index', [
            'serviceRequests' => $requests
        ]);
    }

    // Admin: Approve a request
    public function approve($id)
    {
        $this->authorizeStaff();
        $garageId = \App\Models\Garage::getActiveGarageId();
        $serviceRequest = ServiceRequest::findOrFail($id);
        
        if (auth()->user()->role !== 'super_admin' && (int) $serviceRequest->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        // If already approved, do nothing
        if ($serviceRequest->status === 'approved') {
            return redirect()->back()->with('success', 'Request is already approved.');
        }

        $serviceRequest->update(['status' => 'approved']);
        
        // Check if user already exists
        $user = \App\Models\User::where('email', $serviceRequest->email)->first();
        
        $garageId = clone ($garageId ?: $serviceRequest->garage_id);

        if (!$user) {
            // New user workflow
            $password = \Illuminate\Support\Str::random(8);
            
            $user = \App\Models\User::create([
                'name' => $serviceRequest->customer_name,
                'email' => $serviceRequest->email,
                'phone' => $serviceRequest->phone,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'role' => 'customer',
            ]);
        } else {
            // Existing user workflow
        }

        $customer = \App\Models\Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['garage_id' => $garageId, 'address' => '']
        );

        // Attempt to link vehicle
        $vehicleBrand = \App\Models\VehicleBrand::where('name', $serviceRequest->vehicle_brand)->first();
        $vehicleModel = \App\Models\VehicleModel::where('name', $serviceRequest->vehicle_model)->first();

        $vehicleId = null;
        if ($vehicleBrand && $vehicleModel) {
            $vehicle = \App\Models\Vehicle::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'brand_id' => $vehicleBrand->id,
                    'model_id' => $vehicleModel->id,
                ],
                [
                    'garage_id' => $garageId,
                    'year' => $serviceRequest->vehicle_year,
                    'registration_number' => 'TBD',
                    'vehicle_type' => 'Car',
                    'fuel_type' => 'Petrol',
                ]
            );
            $vehicleId = $vehicle->id;
        }

        // Create Job Card
        if ($vehicleId) {
            $jobCard = \App\Models\JobCard::create([
                'garage_id' => $garageId,
                'job_number' => 'JC-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicleId,
                'status' => 'listed',
                'description' => 'Created from Service Request',
                'vehicle_running_km' => $serviceRequest->vehicle_running_km,
                'fuel_range' => $serviceRequest->fuel_range,
                'created_by' => auth()->id() ?? 1,
            ]);

            $decodedIssues = is_string($serviceRequest->issues) ? json_decode($serviceRequest->issues, true) : $serviceRequest->issues;
            if (is_array($decodedIssues)) {
                foreach ($decodedIssues as $issueData) {
                    \App\Models\JobCardIssue::create([
                        'job_card_id' => $jobCard->id,
                        'description' => $issueData['description'] ?? 'Issue',
                        'reported_by' => 'customer',
                        'images' => !empty($issueData['image_path']) ? [$issueData['image_path']] : null,
                    ]);
                }
            } else {
                \App\Models\JobCardIssue::create([
                    'job_card_id' => $jobCard->id,
                    'description' => $serviceRequest->issues ?? 'Unknown Issue',
                    'reported_by' => 'customer',
                ]);
            }
        }

        return redirect()->back()->with([
            'success' => 'Request approved. Job Card generated & Customer notified.'
        ]);
    }

    // Admin: Decline a request
    public function decline($id)
    {
        $this->authorizeStaff();
        $garageId = \App\Models\Garage::getActiveGarageId();
        $serviceRequest = ServiceRequest::findOrFail($id);

        if (auth()->user()->role !== 'super_admin' && (int) $serviceRequest->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $serviceRequest->update(['status' => 'declined']);

        return redirect()->back()->with('success', 'Request declined.');
    }
}
