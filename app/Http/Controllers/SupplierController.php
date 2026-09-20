<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Garage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
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
        $garageId = Garage::getActiveGarageId();

        $suppliers = Supplier::where('garage_id', $garageId)->orderBy('name')->get();
        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'balance' => 'nullable|numeric',
        ]);

        $validated['garage_id'] = $garageId;

        Supplier::create($validated);

        return redirect()->back()->with('success', 'Supplier added successfully.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $supplier->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'balance' => 'nullable|numeric',
        ]);

        $supplier->update($validated);

        return redirect()->back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $supplier->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $supplier->delete();
        return redirect()->back()->with('success', 'Supplier deleted successfully.');
    }
}
