<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Garage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GarageSettingsController extends Controller
{
    /**
     * Ensure missing columns exist in garages table automatically.
     */
    private function ensureGarageColumnsExist(): void
    {
        try {
            $missing = [];
            if (!Schema::hasColumn('garages', 'tagline')) {
                $missing[] = function ($table) { $table->string('tagline')->nullable(); };
            }
            if (!Schema::hasColumn('garages', 'logo_path')) {
                $missing[] = function ($table) { $table->string('logo_path')->nullable(); };
            }
            if (!Schema::hasColumn('garages', 'header_logo_size')) {
                $missing[] = function ($table) { $table->integer('header_logo_size')->default(32); };
            }
            if (!Schema::hasColumn('garages', 'gst_number')) {
                $missing[] = function ($table) { $table->string('gst_number')->nullable(); };
            }
            if (!Schema::hasColumn('garages', 'bank_details')) {
                $missing[] = function ($table) { $table->text('bank_details')->nullable(); };
            }
            if (!Schema::hasColumn('garages', 'upi_id')) {
                $missing[] = function ($table) { $table->string('upi_id')->nullable(); };
            }
            if (!Schema::hasColumn('garages', 'qr_code_path')) {
                $missing[] = function ($table) { $table->string('qr_code_path')->nullable(); };
            }

            if (!empty($missing)) {
                Schema::table('garages', function ($table) use ($missing) {
                    foreach ($missing as $callback) {
                        $callback($table);
                    }
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Auto-ensuring garage columns warning: ' . $e->getMessage());
        }
    }

    private function authorizeOwnerOrAdmin(): void
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['owner', 'super_admin'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function edit()
    {
        $this->authorizeOwnerOrAdmin();
        $this->ensureGarageColumnsExist();

        $activeGarageId = Garage::getActiveGarageId();
        $garage = $activeGarageId ? Garage::find($activeGarageId) : null;
        
        return Inertia::render('Settings/Edit', [
            'garage' => $garage
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeOwnerOrAdmin();
        $this->ensureGarageColumnsExist();

        $activeGarageId = Garage::getActiveGarageId();
        $garage = $activeGarageId ? Garage::find($activeGarageId) : null;

        if (!$garage) {
            return redirect()->back()->with('error', 'No active garage selected. Please choose a garage first.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'upi_id' => 'nullable|string|max:255',
            'header_logo_size' => 'nullable|integer|min:20|max:120',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048', // 2MB max
            'remove_logo' => 'nullable|boolean',
            'qr_code' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048', // 2MB max
            'remove_qr_code' => 'nullable|boolean',
        ]);

        try {
            $garage->name = $validated['name'];
            $garage->tagline = $validated['tagline'] ?? null;
            $garage->address = $validated['address'] ?? null;
            $garage->gst_number = $validated['gst_number'] ?? null;
            $garage->bank_details = $validated['bank_details'] ?? null;
            $garage->upi_id = $validated['upi_id'] ?? null;
            if (isset($validated['header_logo_size'])) {
                $garage->header_logo_size = (int) $validated['header_logo_size'];
            }

            // Handle removing logo
            if (!empty($validated['remove_logo'])) {
                if ($garage->logo_path) {
                    try {
                        Storage::disk('public')->delete($garage->logo_path);
                        @unlink(public_path('storage/' . $garage->logo_path));
                    } catch (\Throwable $e) {}
                }
                $garage->logo_path = null;
            }

            // Handle uploading new logo
            if ($request->hasFile('logo')) {
                if ($garage->logo_path) {
                    try {
                        Storage::disk('public')->delete($garage->logo_path);
                        @unlink(public_path('storage/' . $garage->logo_path));
                    } catch (\Throwable $e) {}
                }
                $path = $request->file('logo')->store('garages/logos', 'public');
                $garage->logo_path = $path;

                // Also copy directly to public/storage for Hostinger shared hosting symlink resilience
                try {
                    $destination = public_path('storage/' . $path);
                    $destDir = dirname($destination);
                    if (!is_dir($destDir)) {
                        @mkdir($destDir, 0755, true);
                    }
                    @copy(Storage::disk('public')->path($path), $destination);
                } catch (\Throwable $e) {}
            }

            // Handle removing QR code
            if (!empty($validated['remove_qr_code'])) {
                if ($garage->qr_code_path) {
                    try {
                        Storage::disk('public')->delete($garage->qr_code_path);
                        @unlink(public_path('storage/' . $garage->qr_code_path));
                    } catch (\Throwable $e) {}
                }
                $garage->qr_code_path = null;
            }

            // Handle uploading new QR code
            if ($request->hasFile('qr_code')) {
                if ($garage->qr_code_path) {
                    try {
                        Storage::disk('public')->delete($garage->qr_code_path);
                        @unlink(public_path('storage/' . $garage->qr_code_path));
                    } catch (\Throwable $e) {}
                }
                $path = $request->file('qr_code')->store('garages/qrcodes', 'public');
                $garage->qr_code_path = $path;

                // Also copy directly to public/storage for Hostinger shared hosting symlink resilience
                try {
                    $destination = public_path('storage/' . $path);
                    $destDir = dirname($destination);
                    if (!is_dir($destDir)) {
                        @mkdir($destDir, 0755, true);
                    }
                    @copy(Storage::disk('public')->path($path), $destination);
                } catch (\Throwable $e) {}
            }

            $garage->save();

            return redirect()->back()->with('success', 'Garage settings updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Garage settings update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to update garage settings: ' . $e->getMessage());
        }
    }
}

