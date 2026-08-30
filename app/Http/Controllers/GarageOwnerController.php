<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GarageOwnerController extends Controller
{
    public function store(Request $request, Garage $garage)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'owner',
            'garage_id' => $garage->id,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'New owner added successfully.');
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);
        
        // Ensure we are only toggling an owner
        if ($user->role !== 'owner') {
            abort(403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'enabled' : 'disabled';
        return redirect()->back()->with('success', "Owner account {$status} successfully.");
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->role !== 'owner') {
            abort(403);
        }

        $newPassword = $request->input('password') ?: Str::random(12);
        $user->password = Hash::make($newPassword);
        $user->save();

        return redirect()->back()->with([
            'success' => "Password reset for {$user->name}. New Password: {$newPassword}"
        ]);
    }
}
