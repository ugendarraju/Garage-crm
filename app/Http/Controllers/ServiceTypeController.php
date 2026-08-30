<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceTypeController extends Controller
{
    public function index()
    {
        $serviceTypes = ServiceType::where('garage_id', \App\Models\Garage::getActiveGarageId())
            ->latest()
            ->get();

        return Inertia::render('ServiceTypes/Index', [
            'serviceTypes' => $serviceTypes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ServiceType::create([
            'garage_id' => \App\Models\Garage::getActiveGarageId(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Service Type created successfully.');
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        if ($serviceType->garage_id !== \App\Models\Garage::getActiveGarageId()) {
            abort(403);
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
        if ($serviceType->garage_id !== \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $serviceType->update(['is_active' => !$serviceType->is_active]);

        return redirect()->back()->with('success', 'Service Type status updated.');
    }

    public function destroy(ServiceType $serviceType)
    {
        if ($serviceType->garage_id !== \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $serviceType->delete();

        return redirect()->back()->with('success', 'Service Type deleted successfully.');
    }
}
