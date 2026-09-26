<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CustomerController extends Controller
{
    private function authorizeStaffOrOwner(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index()
    {
        $this->authorizeStaffOrOwner();

        $garageId = \App\Models\Garage::getActiveGarageId();
        
        $customers = Customer::where('garage_id', $garageId)
            ->with(['user', 'vehicles', 'jobCards.vehicle.brand', 'jobCards.vehicle.vehicleModel'])
            ->withCount('jobCards')
            ->latest()
            ->get();
        
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
        $this->authorizeStaffOrOwner();

        $garageId = \App\Models\Garage::getActiveGarageId();

        $existingUser = User::where('phone', $request->phone)->first();
        $userId = $existingUser ? $existingUser->id : null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                $userId ? '' : 'unique:users,phone'
            ],
            'email' => 'nullable|email|max:255|unique:users,email' . ($userId ? ',' . $userId : ''),
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'whatsapp_number' => ['nullable', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            
            'vehicles' => 'nullable|array',
            'vehicles.*.registration_number' => 'required|string|max:20',
            'vehicles.*.brand_id' => 'required|exists:vehicle_brands,id',
            'vehicles.*.model_id' => 'required|exists:vehicle_models,id',
        ], [
            'phone.regex' => 'The phone number must contain 7 to 15 digits (digits and optional leading + only).',
            'whatsapp_number.regex' => 'The WhatsApp number must contain 7 to 15 digits.',
        ]);

        $tempPassword = null;
        $isNewUser = false;
        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            $isNewUser = true;
            $tempPassword = 'PC#' . rand(100000, 999999);
            
            \App\Services\DatabaseSchemaAutoFixService::ensureSchemaIsUpToDate();

            $userData = [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? 'customer_' . preg_replace('/[^0-9]/', '', $validated['phone']) . '@example.com',
                'role' => 'customer',
                'password' => bcrypt($tempPassword),
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'must_change_password')) {
                $userData['must_change_password'] = true;
            }

            $user = User::create($userData);
        } else {
            // Update email if provided and user has placeholder email
            if (!empty($validated['email']) && (str_contains($user->email, 'customer_') || empty($user->email))) {
                $user->update(['email' => $validated['email']]);
            }
        }

        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id, 'garage_id' => $garageId],
            [
                'address' => $validated['address'] ?? null,
                'gst_number' => $validated['gst_number'] ?? null,
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

        $inviteSent = false;
        if ($isNewUser && !empty($validated['email']) && !str_contains($validated['email'], 'customer_')) {
            try {
                $garage = \App\Models\Garage::find($garageId);
                \Illuminate\Support\Facades\Mail::to($validated['email'])->send(
                    new \App\Mail\CustomerInvitationMail($user, $tempPassword, $garage)
                );
                $inviteSent = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send customer invitation email: " . $e->getMessage());
            }
        }

        \App\Services\ActionLogService::log(
            action: 'create',
            module: 'customers',
            description: "Added customer '{$user->name}' ({$user->phone})",
            garageId: $garageId,
            details: [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $validated['email'] ?? null,
                'invite_sent' => $inviteSent,
                'is_new_user' => $isNewUser,
            ]
        );

        $flashData = [
            'success' => $isNewUser 
                ? ($inviteSent 
                    ? "Customer added successfully! Invitation email with temporary password has been sent to {$validated['email']}."
                    : "Customer added successfully!")
                : "Customer added to garage successfully.",
        ];

        if ($isNewUser && $tempPassword) {
            $flashData['new_customer_credentials'] = [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => !empty($validated['email']) ? $validated['email'] : null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? $validated['phone'],
                'temporary_password' => $tempPassword,
                'invite_sent' => $inviteSent,
            ];
        }

        return redirect()->back()->with($flashData);
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeStaffOrOwner();

        $garageId = \App\Models\Garage::getActiveGarageId();
        if ((int) $customer->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized action.');
        }

        $userId = $customer->user_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                'unique:users,phone,' . $userId,
            ],
            'email' => 'nullable|email|max:255|unique:users,email,' . $userId,
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'whatsapp_number' => ['nullable', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            
            'vehicles' => 'nullable|array',
            'vehicles.*.id' => 'nullable|exists:vehicles,id',
            'vehicles.*.registration_number' => 'required|string|max:20',
            'vehicles.*.brand_id' => 'required|exists:vehicle_brands,id',
            'vehicles.*.model_id' => 'required|exists:vehicle_models,id',
        ], [
            'phone.regex' => 'The phone number must contain 7 to 15 digits (digits and optional leading + only).',
            'whatsapp_number.regex' => 'The WhatsApp number must contain 7 to 15 digits.',
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
        $this->authorizeStaffOrOwner();

        $garageId = \App\Models\Garage::getActiveGarageId();
        if ((int) $customer->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized action.');
        }

        $userId = $customer->user_id;
        $customer->delete();
        User::find($userId)?->delete();
        
        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }

    public function resetPassword(Customer $customer)
    {
        $this->authorizeStaffOrOwner();

        $garageId = \App\Models\Garage::getActiveGarageId();
        if ((int) $customer->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized action.');
        }

        $temporaryPassword = 'PC#' . rand(100000, 999999);
        \App\Services\DatabaseSchemaAutoFixService::ensureSchemaIsUpToDate();

        $updateData = [
            'password' => bcrypt($temporaryPassword),
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'must_change_password')) {
            $updateData['must_change_password'] = true;
        }
        $customer->user->update($updateData);

        $inviteSent = false;
        $email = $customer->user->email;
        if (!empty($email) && !str_contains($email, 'customer_')) {
            try {
                $garage = \App\Models\Garage::find($garageId);
                \Illuminate\Support\Facades\Mail::to($email)->send(
                    new \App\Mail\CustomerInvitationMail($customer->user, $temporaryPassword, $garage)
                );
                $inviteSent = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send customer password reset email: " . $e->getMessage());
            }
        }

        \App\Services\ActionLogService::log(
            action: 'reset_password',
            module: 'customers',
            description: "Reset password for customer '{$customer->user->name}' ({$customer->user->phone})",
            garageId: $garageId,
            details: ['phone' => $customer->user->phone, 'invite_sent' => $inviteSent]
        );

        return redirect()->back()->with([
            'success' => $inviteSent 
                ? "Password reset successfully and email sent to {$email}."
                : "Password reset successfully.",
            'new_customer_credentials' => [
                'name' => $customer->user->name,
                'phone' => $customer->user->phone,
                'email' => !str_contains($email, 'customer_') ? $email : null,
                'whatsapp_number' => $customer->whatsapp_number ?? $customer->user->phone,
                'temporary_password' => $temporaryPassword,
                'invite_sent' => $inviteSent,
                'is_reset' => true,
            ]
        ]);
    }
}
