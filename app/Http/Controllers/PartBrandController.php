<?php

namespace App\Http\Controllers;

use App\Models\PartBrand;
use Illuminate\Http\Request;

class PartBrandController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        PartBrand::create($validated);

        return redirect()->back()->with('success', 'Brand added successfully.');
    }

    public function destroy(PartBrand $partBrand)
    {
        $partBrand->delete();
        return redirect()->back()->with('success', 'Brand deleted successfully.');
    }
}
