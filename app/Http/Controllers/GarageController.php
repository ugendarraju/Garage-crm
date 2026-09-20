<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class GarageController extends Controller
{
    private function authorizeSuperAdmin(): void
    {
        if (!auth()->check() || auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }
    }

    // SuperAdmin only
    public function index()
    {
        $this->authorizeSuperAdmin();

        $garages = Garage::with('owners')->orderBy('created_at', 'desc')->get();
        // Since SuperAdmin doesn't need to see job cards here, we just list garages.
        return Inertia::render('SuperAdmin/Garages/Index', [
            'garages' => $garages
        ]);
    }

    public function toggleActive($id)
    {
        $this->authorizeSuperAdmin();

        $garage = Garage::findOrFail($id);
        $garage->is_active = !$garage->is_active;
        $garage->save();
        
        $status = $garage->is_active ? 'enabled' : 'disabled';
        return redirect()->back()->with('success', "Garage {$status} successfully.");
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $garage = Garage::create([
            'name' => $request->name,
            'is_active' => true,
        ]);

        User::create([
            'name' => $request->owner_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'owner',
            'garage_id' => $garage->id,
        ]);

        return redirect()->back()->with('success', 'Garage and Owner created successfully.');
    }

    public function destroy($id)
    {
        $this->authorizeSuperAdmin();

        $garage = Garage::findOrFail($id);
        $garage->delete();
        return redirect()->back()->with('success', 'Garage deleted successfully.');
    }
}
