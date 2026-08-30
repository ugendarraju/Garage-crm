<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Garage;
use Illuminate\Support\Facades\Storage;

class GarageSettingsController extends Controller
{
    public function edit()
    {
        $garage = Garage::find(Garage::getActiveGarageId());
        
        return Inertia::render('Settings/Edit', [
            'garage' => $garage
        ]);
    }

    public function update(Request $request)
    {
        $garage = Garage::find(Garage::getActiveGarageId());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'upi_id' => 'nullable|string|max:255',
            'header_logo_size' => 'nullable|integer|min:20|max:120',
            'logo' => 'nullable|image|max:2048', // 2MB max
            'qr_code' => 'nullable|image|max:2048', // 2MB max
        ]);

        $garage->name = $validated['name'];
        $garage->tagline = $validated['tagline'] ?? null;
        $garage->address = $validated['address'] ?? null;
        $garage->gst_number = $validated['gst_number'] ?? null;
        $garage->bank_details = $validated['bank_details'] ?? null;
        $garage->upi_id = $validated['upi_id'] ?? null;
        if (isset($validated['header_logo_size'])) {
            $garage->header_logo_size = $validated['header_logo_size'];
        }

        if ($request->hasFile('logo')) {
            if ($garage->logo_path) {
                Storage::disk('public')->delete($garage->logo_path);
            }
            $garage->logo_path = $request->file('logo')->store('garages/logos', 'public');
        }

        if ($request->hasFile('qr_code')) {
            if ($garage->qr_code_path) {
                Storage::disk('public')->delete($garage->qr_code_path);
            }
            $garage->qr_code_path = $request->file('qr_code')->store('garages/qrcodes', 'public');
        }

        $garage->save();

        return redirect()->back()->with('success', 'Garage settings updated successfully.');
    }
}
