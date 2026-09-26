<?php

namespace App\Http\Controllers;

use App\Models\PartCategory;
use App\Models\Garage;
use Illuminate\Http\Request;

class PartCategoryController extends Controller
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
            'description' => 'nullable|string',
        ]);

        $validated['garage_id'] = $garageId;

        PartCategory::create($validated);

        return redirect()->back()->with('success', 'Category added successfully.');
    }

    public function destroy(PartCategory $partCategory)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $partCategory->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $partCategory->delete();
        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
