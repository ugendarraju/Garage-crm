<?php

namespace App\Http\Controllers;

use App\Models\PartBrand;
use App\Models\Garage;
use Illuminate\Http\Request;

class PartBrandController extends Controller
{
    private function authorizeStaff(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index()
    {
        return redirect()->route('inventory.index');
    }

    public function store(Request $request)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $validated['garage_id'] = $garageId;

        PartBrand::create($validated);

        return redirect()->back()->with('success', 'Brand added successfully.');
    }

    public function destroy(PartBrand $partBrand)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $partBrand->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $partBrand->delete();
        return redirect()->back()->with('success', 'Brand deleted successfully.');
    }
}
