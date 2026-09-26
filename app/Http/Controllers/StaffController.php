<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    private function authorizeStaffManager(): void
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['owner', 'super_admin'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index()
    {
        $this->authorizeStaffManager();

        $staff = User::whereIn('role', ['mechanic', 'manager'])
            ->where('garage_id', \App\Models\Garage::getActiveGarageId())
            ->latest()
            ->get();

        return Inertia::render('Staff/Index', [
            'staff' => $staff
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeStaffManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'role' => ['required', Rule::in(['mechanic', 'manager'])],
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'garage_id' => \App\Models\Garage::getActiveGarageId(),
            'is_active' => true,
        ]);

        \App\Services\ActionLogService::log(
            action: 'created',
            module: 'staff',
            description: "Added staff member '{$user->name}' (Role: {$user->role})",
            details: ['staff_id' => $user->id, 'role' => $user->role, 'email' => $user->email]
        );

        return redirect()->back()->with('success', 'Staff member added successfully.');
    }

    public function update(Request $request, User $staff)
    {
        $this->authorizeStaffManager();

        // Ensure staff belongs to active garage
        if ($staff->garage_id !== \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($staff->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'role' => ['required', Rule::in(['mechanic', 'manager'])],
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $staff->update($data);

        \App\Services\ActionLogService::log(
            action: 'updated',
            module: 'staff',
            description: "Updated staff member '{$staff->name}' (Role: {$staff->role})",
            details: ['staff_id' => $staff->id, 'role' => $staff->role]
        );

        return redirect()->back()->with('success', 'Staff member updated successfully.');
    }

    public function toggleActive(User $staff)
    {
        $this->authorizeStaffManager();

        if ($staff->garage_id !== \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $staff->update(['is_active' => !$staff->is_active]);
        $status = $staff->is_active ? 'enabled' : 'disabled';

        \App\Services\ActionLogService::log(
            action: $status,
            module: 'staff',
            description: "Toggled staff member '{$staff->name}' status to {$status}",
            details: ['staff_id' => $staff->id, 'is_active' => $staff->is_active]
        );

        return redirect()->back()->with('success', 'Staff status updated successfully.');
    }

    public function destroy(User $staff)
    {
        $this->authorizeStaffManager();

        if ($staff->garage_id !== \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $name = $staff->name;
        $role = $staff->role;

        \App\Services\ActionLogService::log(
            action: 'deleted',
            module: 'staff',
            description: "Removed staff member '{$name}' ({$role})",
            details: ['staff_id' => $staff->id, 'name' => $name, 'role' => $role]
        );

        $staff->delete();

        return redirect()->back()->with('success', 'Staff member removed successfully.');
    }
}
