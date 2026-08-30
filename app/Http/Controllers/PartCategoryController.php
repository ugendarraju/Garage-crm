<?php

namespace App\Http\Controllers;

use App\Models\PartCategory;
use Illuminate\Http\Request;

class PartCategoryController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        PartCategory::create($validated);

        return redirect()->back()->with('success', 'Category added successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PartCategory $partCategory)
    {
        $partCategory->delete();
        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
