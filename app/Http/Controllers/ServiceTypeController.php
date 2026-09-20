<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use App\Models\Garage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceTypeController extends Controller
{
    private function authorizeStaff(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index()
    {
        $this->authorizeStaff();
        $serviceTypes = ServiceType::where('garage_id', Garage::getActiveGarageId())
            ->latest()
            ->get();

        return Inertia::render('ServiceTypes/Index', [
            'serviceTypes' => $serviceTypes
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ServiceType::create([
            'garage_id' => Garage::getActiveGarageId(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Service Type created successfully.');
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $this->authorizeStaff();

        if (auth()->user()->role !== 'super_admin' && (int) $serviceType->garage_id !== (int) Garage::getActiveGarageId()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $serviceType->update($validated);

        return redirect()->back()->with('success', 'Service Type updated successfully.');
    }

    public function toggleActive(ServiceType $serviceType)
    {
        $this->authorizeStaff();

        if (auth()->user()->role !== 'super_admin' && (int) $serviceType->garage_id !== (int) Garage::getActiveGarageId()) {
            abort(403, 'Unauthorized access.');
        }

        $serviceType->update(['is_active' => !$serviceType->is_active]);

        return redirect()->back()->with('success', 'Service Type status updated.');
    }

    public function destroy(ServiceType $serviceType)
    {
        $this->authorizeStaff();

        if (auth()->user()->role !== 'super_admin' && (int) $serviceType->garage_id !== (int) Garage::getActiveGarageId()) {
            abort(403, 'Unauthorized access.');
        }

        $serviceType->delete();

        return redirect()->back()->with('success', 'Service Type deleted successfully.');
    }
}
