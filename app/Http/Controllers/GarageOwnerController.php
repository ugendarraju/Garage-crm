<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GarageOwnerController extends Controller
{
    private function authorizeSuperAdmin(): void
    {
        if (!auth()->check() || auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function store(Request $request, Garage $garage)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'owner',
            'garage_id' => $garage->id,
            'is_active' => true,
        ]);

        \App\Services\ActionLogService::log(
            action: 'created',
            module: 'staff',
            description: "Added owner '{$user->name}' ({$user->email}) to garage '{$garage->name}'",
            garageId: $garage->id,
            details: ['email' => $user->email, 'role' => 'owner']
        );

        return redirect()->back()->with('success', 'New owner added successfully.');
    }

    public function toggleActive($id)
    {
        $this->authorizeSuperAdmin();

        $user = User::findOrFail($id);
        
        // Ensure we are only toggling an owner
        if ($user->role !== 'owner') {
            abort(403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'enabled' : 'disabled';
        \App\Services\ActionLogService::log(
            action: $user->is_active ? 'enabled' : 'disabled',
            module: 'staff',
            description: "Toggled owner '{$user->name}' status to {$status}",
            garageId: $user->garage_id
        );

        return redirect()->back()->with('success', "Owner account {$status} successfully.");
    }

    public function resetPassword(Request $request, $id)
    {
        $this->authorizeSuperAdmin();

        $user = User::findOrFail($id);
        
        if ($user->role !== 'owner') {
            abort(403);
        }

        $newPassword = $request->input('password') ?: Str::random(12);
        $user->password = Hash::make($newPassword);
        $user->save();

        \App\Services\ActionLogService::log(
            action: 'reset_password',
            module: 'auth',
            description: "Reset password for garage owner '{$user->name}'",
            garageId: $user->garage_id
        );

        return redirect()->back()->with([
            'success' => "Password reset for {$user->name}. New Password: {$newPassword}"
        ]);
    }
}
