<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class JobCardController extends Controller
{
    public function index()
    {
        $query = JobCard::with(['customer.user', 'vehicle.brand', 'vehicle.vehicleModel', 'mechanics', 'garage']);

        if (auth()->user()->role === 'customer') {
            $customerProfile = auth()->user()->customerProfile;
            if ($customerProfile) {
                $query->where('customer_id', $customerProfile->id);
            } else {
                $query->where('id', 0);
            }
        } else {
            $query->where('garage_id', \App\Models\Garage::getActiveGarageId());
        }

        $jobCards = $query->latest()->get();
            
        return Inertia::render('JobCards/Index', [
            'jobCards' => $jobCards
        ]);
    }

    public function create()
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }

        $garageId = \App\Models\Garage::getActiveGarageId();
        
        return Inertia::render('JobCards/Create', [
            'customers' => Customer::where('garage_id', $garageId)->with('user', 'vehicles')->get(),
            'mechanics' => User::where('garage_id', $garageId)
                               ->whereIn('role', ['mechanic', 'manager'])
                               ->where('is_active', true)->get(),
            'serviceTypes' => \App\Models\ServiceType::where('garage_id', $garageId)
                               ->where('is_active', true)->get(),
            'brands' => \App\Models\VehicleBrand::where(function($query) use ($garageId) {
                               $query->where('garage_id', $garageId)->orWhereNull('garage_id');
                           })->get(),
            'models' => \App\Models\VehicleModel::where(function($query) use ($garageId) {
                               $query->where('garage_id', $garageId)->orWhereNull('garage_id');
                           })->with('brand')->get(),
            'inventoryItems' => \App\Models\Inventory::where('garage_id', $garageId)->orderBy('part_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }

        $garageId = \App\Models\Garage::getActiveGarageId();

        $validated = $request->validate([
            // Customer & Vehicle
            'customer_id' => 'nullable|exists:customers,id',
            'new_customer_name' => 'nullable|string|max:255',
            'new_customer_phone' => 'nullable|string|max:20',
            
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'new_vehicle_reg' => 'nullable|string|max:20',
            'new_vehicle_brand_id' => 'nullable|exists:vehicle_brands,id',
            'new_vehicle_model_id' => 'nullable|exists:vehicle_models,id',
            
            // Job Details
            'mechanic_ids' => 'nullable|array',
            'mechanic_ids.*' => 'exists:users,id',
            
            'job_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'priority' => 'nullable|in:Low,Medium,High',
            'status' => 'required|in:scheduled,listed,working,complete,completed,delivered',
            'next_service_reminder' => 'nullable|date',
            'vehicle_running_km' => 'nullable|string',
            'fuel_range' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string',
            
            'description' => 'nullable|string',
            
            // Issues
            'customer_issues' => 'nullable|array',
            'customer_issues.*.id' => 'nullable|integer',
            'customer_issues.*.description' => 'required|string',
            'customer_issues.*.status' => 'nullable|string',
            'customer_issues.*.reported_by' => 'nullable|string',
            'customer_issues.*.requires_consent' => 'nullable',
            'customer_issues.*.consent_status' => 'nullable|string',
            'customer_issues.*.mechanic_notes' => 'nullable|string',
            'customer_issues.*.customer_notes' => 'nullable|string',
            'customer_issues.*.new_images' => 'nullable|array',
            'customer_issues.*.new_images.*' => 'image|max:5120',
            'customer_issues.*.existing_images' => 'nullable|array',
            'customer_issues.*.existing_images.*' => 'string',
            
            // Services
            'services' => 'nullable|array',
            'services.*.type' => 'required|in:Service,Part',
            'services.*.name' => 'required|string',
            'services.*.rate' => 'required|numeric',
            'services.*.apply_gst' => 'nullable|boolean',
            'services.*.gst_percent' => 'nullable|string',
        ]);

        // Resolve Customer
        $customerId = $validated['customer_id'] ?? null;
        if (!$customerId && !empty($validated['new_customer_phone'])) {
            $user = User::firstOrCreate(
                ['phone' => $validated['new_customer_phone']],
                [
                    'name' => $validated['new_customer_name'] ?? 'Walk-in',
                    'email' => 'customer_' . $validated['new_customer_phone'] . '@example.com',
                    'role' => 'customer',
                    'password' => bcrypt(\Illuminate\Support\Str::random(12)),
                    'garage_id' => clone $garageId, // They might belong anywhere but we track them here
                ]
            );
            $customer = Customer::firstOrCreate(['user_id' => $user->id]);
            $customerId = $customer->id;
        }

        // Resolve Vehicle
        $vehicleId = $validated['vehicle_id'] ?? null;
        if (!$vehicleId && !empty($validated['new_vehicle_reg'])) {
            $vehicle = Vehicle::firstOrCreate(
                ['registration_number' => $validated['new_vehicle_reg']],
                [
                    'customer_id' => $customerId,
                    'brand_id' => $validated['new_vehicle_brand_id'],
                    'model_id' => $validated['new_vehicle_model_id'],
                    'vehicle_type' => 'Car',
                    'fuel_type' => 'Petrol',
                ]
            );
            $vehicleId = $vehicle->id;
        }

        if (!$customerId || !$vehicleId) {
            return back()->withErrors(['customer_id' => 'Customer and Vehicle are required.']);
        }

        // 3. Create Job Card
        $jobCard = JobCard::create([
            'garage_id' => $garageId,
            'job_number' => 'JC-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'description' => $validated['description'] ?? '',
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'priority' => $validated['priority'] ?? 'Medium',
            'status' => $validated['status'] ?? 'listed',
            'next_service_reminder' => $validated['next_service_reminder'] ?? null,
            'vehicle_running_km' => $validated['vehicle_running_km'] ?? null,
            'fuel_range' => $validated['fuel_range'] ?? null,
            'amount_paid' => $validated['amount_paid'] ?? 0,
            'discount' => $validated['discount'] ?? 0,
            'coupon_code' => $validated['coupon_code'] ?? null,
            'created_by' => auth()->id() ?? 1,
        ]);

        if (!empty($validated['mechanic_ids'])) {
            $syncResult = $jobCard->mechanics()->sync($validated['mechanic_ids']);
            if (!empty($syncResult['attached'])) {
                foreach ($syncResult['attached'] as $mechId) {
                    $mech = User::find($mechId);
                    // Notifications disabled for mechanics in this iteration
                }
            }
        }

        $this->syncIssues($jobCard, $validated['customer_issues'] ?? null);

        $subtotal = 0;
        $finalTotal = 0;

        if (!empty($validated['services'])) {
            foreach ($validated['services'] as $svc) {
                if (!empty($svc['name'])) {
                    $rate = (float) $svc['rate'];
                    $total = $rate;
                    if (!empty($svc['apply_gst']) && $svc['apply_gst'] === true) {
                        $percent = $this->extractGstRate($svc['gst_percent'] ?? '18%');
                        $total = $rate + ($rate * ($percent / 100));
                    }
                    
                    $subtotal += $rate;
                    $finalTotal += $total;

                    $jobCard->items()->create([
                        'garage_id' => $garageId,
                        'item_type' => $svc['type'] === 'Service' ? 'service' : 'spare_part',
                        'item_name' => $svc['name'],
                        'unit_price' => $rate,
                        'quantity' => 1,
                        'total' => $total,
                        'gst_percent' => !empty($svc['apply_gst']) && $svc['apply_gst'] === true ? ($svc['gst_percent'] ?? '18%') : null,
                    ]);
                }
            }
        }

        $jobCard->update([
            'subtotal' => $subtotal,
            'final_total' => max(0, $finalTotal - ($jobCard->discount ?? 0)),
        ]);

        return redirect()->route('job-cards.index')->with('success', 'Job Card created successfully.');
    }

    private function extractGstRate($gstString)
    {
        if (empty($gstString) || $gstString === 'None') return 0;
        
        if (str_contains($gstString, 'CGST')) {
            if (preg_match('/CGST (\d+(\.\d+)?)%/', $gstString, $matches)) {
                return ((float) $matches[1]) * 2;
            }
        } elseif (str_contains($gstString, 'IGST')) {
            if (preg_match('/IGST (\d+(\.\d+)?)%/', $gstString, $matches)) {
                return (float) $matches[1];
            }
        }
        
        return (int) str_replace('%', '', $gstString);
    }

    public function show(JobCard $jobCard)
    {
        if (auth()->user()->role === 'customer') {
            if (!$jobCard->customer_id || auth()->user()->customerProfile?->id !== $jobCard->customer_id) {
                abort(403, 'Unauthorized access.');
            }
        } else {
            if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
                abort(403, 'Unauthorized access.');
            }
        }

        $jobCard->load(['customer.user', 'vehicle.brand', 'vehicle.vehicleModel', 'items', 'issues', 'createdBy', 'mechanics', 'garage']);
        return Inertia::render('JobCards/Show', [
            'jobCard' => $jobCard
        ]);
    }

    public function edit(JobCard $jobCard)
    {
        $isCustomer = auth()->user()->role === 'customer';
        if ($isCustomer) {
            if (!$jobCard->customer_id || auth()->user()->customerProfile?->id !== $jobCard->customer_id) {
                abort(403, 'Unauthorized access.');
            }
        } else {
            if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
                abort(403, 'Unauthorized access.');
            }
        }

        $garageId = $jobCard->garage_id;
        $jobCard->load(['customer.user', 'vehicle.brand', 'vehicle.vehicleModel', 'items', 'issues', 'mechanics']);

        return Inertia::render('JobCards/Edit', [
            'jobCard' => $jobCard,
            'customers' => $isCustomer ? [] : Customer::where('garage_id', $garageId)->with('user', 'vehicles')->get(),
            'mechanics' => $isCustomer ? [] : User::where('garage_id', $garageId)
                               ->whereIn('role', ['mechanic', 'manager'])
                               ->where('is_active', true)->get(),
            'serviceTypes' => $isCustomer ? [] : \App\Models\ServiceType::where('garage_id', $garageId)
                               ->where('is_active', true)->get(),
            'brands' => $isCustomer ? [] : \App\Models\VehicleBrand::where(function($query) use ($garageId) {
                               $query->where('garage_id', $garageId)->orWhereNull('garage_id');
                           })->get(),
            'models' => $isCustomer ? [] : \App\Models\VehicleModel::where(function($query) use ($garageId) {
                               $query->where('garage_id', $garageId)->orWhereNull('garage_id');
                           })->with('brand')->get(),
            'inventoryItems' => $isCustomer ? [] : \App\Models\Inventory::where('garage_id', $garageId)->orderBy('part_name')->get(),
        ]);
    }

    public function update(Request $request, JobCard $jobCard)
    {
        $garageId = \App\Models\Garage::getActiveGarageId();
        $isCustomer = auth()->user()->role === 'customer';

        if ($isCustomer) {
            if (!$jobCard->customer_id || auth()->user()->customerProfile?->id !== $jobCard->customer_id) {
                abort(403, 'Unauthorized access.');
            }

            // Customers may ONLY update issue notes and consent statuses
            $validated = $request->validate([
                'customer_issues' => 'nullable|array',
                'customer_issues.*.id' => 'nullable|integer',
                'customer_issues.*.consent_status' => 'nullable|in:pending,approved,denied',
                'customer_issues.*.customer_notes' => 'nullable|string',
            ]);

            if (!empty($validated['customer_issues'])) {
                foreach ($validated['customer_issues'] as $issueData) {
                    if (!empty($issueData['id'])) {
                        $issue = $jobCard->issues()->where('id', $issueData['id'])->first();
                        if ($issue) {
                            $updateData = [];
                            if (array_key_exists('consent_status', $issueData)) {
                                $updateData['consent_status'] = $issueData['consent_status'];
                            }
                            if (array_key_exists('customer_notes', $issueData)) {
                                $updateData['customer_notes'] = $issueData['customer_notes'];
                            }
                            if (!empty($updateData)) {
                                $issue->update($updateData);
                            }
                        }
                    }
                }
            }

            return redirect()->route('job-cards.index')->with(['success' => 'Job Card updated.']);
        }

        if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        \Log::info('JobCard update request: ', $request->all());

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'new_customer_name' => 'nullable|string|max:255',
            'new_customer_phone' => 'nullable|string|max:20',
            
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'new_vehicle_reg' => 'nullable|string|max:20',
            'new_vehicle_brand_id' => 'nullable|exists:vehicle_brands,id',
            'new_vehicle_model_id' => 'nullable|exists:vehicle_models,id',
            
            'mechanic_ids' => 'nullable|array',
            'mechanic_ids.*' => 'exists:users,id',
            
            'expected_delivery_date' => 'nullable|date',
            'priority' => 'nullable|in:Low,Medium,High',
            'status' => 'required|in:scheduled,listed,working,complete,completed,delivered',
            'next_service_reminder' => 'nullable|date',
            'vehicle_running_km' => 'nullable|string',
            'fuel_range' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string',
            
            'description' => 'nullable|string',
            
            'customer_issues' => 'nullable|array',
            'customer_issues.*.id' => 'nullable|integer',
            'customer_issues.*.description' => 'required|string',
            'customer_issues.*.status' => 'nullable|string',
            'customer_issues.*.reported_by' => 'nullable|string',
            'customer_issues.*.requires_consent' => 'nullable',
            'customer_issues.*.consent_status' => 'nullable|string',
            'customer_issues.*.mechanic_notes' => 'nullable|string',
            'customer_issues.*.customer_notes' => 'nullable|string',
            'customer_issues.*.new_images' => 'nullable|array',
            'customer_issues.*.new_images.*' => 'image|max:5120',
            'customer_issues.*.existing_images' => 'nullable|array',
            'customer_issues.*.existing_images.*' => 'string',
            
            'services' => 'nullable|array',
            'services.*.type' => 'required|in:Service,Part',
            'services.*.name' => 'required|string',
            'services.*.rate' => 'required|numeric',
            'services.*.apply_gst' => 'nullable|boolean',
            'services.*.gst_percent' => 'nullable|string',
        ]);

        $customerId = $validated['customer_id'] ?? $jobCard->customer_id;
        $vehicleId = $validated['vehicle_id'] ?? $jobCard->vehicle_id;

        $jobCard->update([
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'description' => $validated['description'] ?? '',
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'priority' => $validated['priority'] ?? 'Medium',
            'status' => $validated['status'] ?? 'listed',
            'next_service_reminder' => $validated['next_service_reminder'] ?? null,
            'vehicle_running_km' => $validated['vehicle_running_km'] ?? null,
            'fuel_range' => $validated['fuel_range'] ?? null,
            'amount_paid' => $validated['amount_paid'] ?? 0,
            'discount' => $validated['discount'] ?? 0,
            'coupon_code' => $validated['coupon_code'] ?? null,
        ]);

        if (isset($validated['mechanic_ids'])) {
            $syncResult = $jobCard->mechanics()->sync($validated['mechanic_ids']);
            if (!empty($syncResult['attached'])) {
                foreach ($syncResult['attached'] as $mechId) {
                    $mech = User::find($mechId);
                    // Notifications disabled for mechanics in this iteration
                }
            }
        } else {
            $jobCard->mechanics()->detach();
        }

        $this->syncIssues($jobCard, $validated['customer_issues'] ?? null);

        $subtotal = 0;
        $finalTotal = 0;

        $jobCard->items()->delete();
        if (!empty($validated['services'])) {
            foreach ($validated['services'] as $svc) {
                if (!empty($svc['name'])) {
                    $rate = (float) $svc['rate'];
                    $total = $rate;
                    if (!empty($svc['apply_gst']) && $svc['apply_gst'] === true) {
                        $percent = $this->extractGstRate($svc['gst_percent'] ?? '18%');
                        $total = $rate + ($rate * ($percent / 100));
                    }
                    
                    $subtotal += $rate;
                    $finalTotal += $total;

                    $jobCard->items()->create([
                        'garage_id' => $jobCard->garage_id,
                        'item_type' => $svc['type'] === 'Service' ? 'service' : 'spare_part',
                        'item_name' => $svc['name'],
                        'unit_price' => $rate,
                        'quantity' => 1,
                        'total' => $total,
                        'gst_percent' => !empty($svc['apply_gst']) && $svc['apply_gst'] === true ? ($svc['gst_percent'] ?? '18%') : null,
                    ]);
                }
            }
        }

        $jobCard->update([
            'subtotal' => $subtotal,
            'final_total' => max(0, $finalTotal - ($jobCard->discount ?? 0)),
        ]);

        return redirect()->route('job-cards.index')->with([
            'success' => 'Job Card updated.'
        ]);
    }

    public function destroy(JobCard $jobCard)
    {
        if (auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }

        if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
            abort(403, 'Unauthorized access.');
        }
        $jobCard->delete();
        return redirect()->route('job-cards.index')->with('success', 'Job Card deleted.');
    }

    public function updateStatus(Request $request, JobCard $jobCard)
    {
        if (auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }

        if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'status' => 'required|in:scheduled,listed,working,complete,completed,delivered'
        ]);

        $jobCard->status = $validated['status'];
        if ($validated['status'] === 'delivered' && !$jobCard->delivered_at) {
            $jobCard->delivered_at = now();
        }
        $jobCard->save();

        return redirect()->back()->with([
            'success' => 'Status updated successfully.'
        ]);
    }

    private function syncIssues(JobCard $jobCard, ?array $issues)
    {
        if ($issues === null) {
            $jobCard->issues()->delete();
            return;
        }

        $existingIssueIds = $jobCard->issues()->pluck('id')->toArray();
        $receivedIssueIds = collect($issues)->pluck('id')->filter()->toArray();

        $idsToDelete = array_diff($existingIssueIds, $receivedIssueIds);
        if (!empty($idsToDelete)) {
            $jobCard->issues()->whereIn('id', $idsToDelete)->delete();
        }

        foreach ($issues as $issueData) {
            if (empty($issueData['description'])) {
                continue;
            }

            $issueId = $issueData['id'] ?? null;
            $issueModel = $issueId ? \App\Models\JobCardIssue::find($issueId) : null;

            $images = [];
            if (!empty($issueData['new_images']) && is_array($issueData['new_images'])) {
                foreach ($issueData['new_images'] as $image) {
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        $path = $image->store('issues', 'public');
                        $images[] = '/storage/' . $path;
                    }
                }
            }
            
            $existingImages = [];
            if ($issueModel && $issueModel->images) {
                $existingImages = $issueModel->images;
                if (isset($issueData['existing_images']) && is_array($issueData['existing_images'])) {
                    $existingImages = array_intersect($existingImages, $issueData['existing_images']);
                }
            }

            $finalImages = array_values(array_merge($existingImages, $images));
            if (empty($finalImages)) {
                $finalImages = null;
            }

            $data = [
                'description' => $issueData['description'],
                'status' => $issueData['status'] ?? 'Pending',
                'reported_by' => $issueData['reported_by'] ?? 'customer',
                'requires_consent' => filter_var($issueData['requires_consent'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'consent_status' => $issueData['consent_status'] ?? null,
                'mechanic_notes' => $issueData['mechanic_notes'] ?? null,
                'customer_notes' => $issueData['customer_notes'] ?? null,
                'images' => $finalImages,
            ];

            if ($issueModel) {
                $issueModel->update($data);
            } else {
                $jobCard->issues()->create($data);
            }
        }
    }

    public function invoice(JobCard $jobCard)
    {
        if (auth()->user()->role === 'customer') {
            if (!$jobCard->customer_id || auth()->user()->customerProfile?->id !== $jobCard->customer_id) {
                abort(403, 'Unauthorized access.');
            }
        } else {
            if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
                abort(403, 'Unauthorized access.');
            }
        }

        $jobCard->load(['customer.user', 'vehicle.brand', 'vehicle.vehicleModel', 'items', 'issues', 'garage.owner']);

        return Inertia::render('JobCards/Invoice', [
            'jobCard' => $jobCard
        ]);
    }

    public function uploadPaymentScreenshot(Request $request, JobCard $jobCard)
    {
        if (auth()->user()->role === 'customer') {
            if (!$jobCard->customer_id || auth()->user()->customerProfile?->id !== $jobCard->customer_id) {
                abort(403, 'Unauthorized access.');
            }
        } else {
            if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
                abort(403, 'Unauthorized access.');
            }
        }

        $request->validate([
            'payment_screenshot' => 'required|image|max:5120', // 5MB max
        ]);

        if ($request->hasFile('payment_screenshot')) {
            if ($jobCard->payment_screenshot_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($jobCard->payment_screenshot_path);
            }
            $path = $request->file('payment_screenshot')->store('payments', 'public');
            $jobCard->update(['payment_screenshot_path' => $path]);
        }

        return redirect()->back()->with('success', 'Payment screenshot uploaded successfully.');
    }

    public function updatePaymentStatus(Request $request, JobCard $jobCard)
    {
        if (auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }

        if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'payment_status' => 'required|string|in:paid,pending',
        ]);

        $jobCard->update(['payment_status' => $validated['payment_status']]);

        return redirect()->back()->with('success', 'Payment status updated successfully.');
    }
}
