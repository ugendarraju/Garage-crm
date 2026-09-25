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
            if (!Schema::hasColumn('garages', 'slug')) {
                $missing[] = function ($table) { $table->string('slug')->nullable()->unique(); };
            }
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
            if (!Schema::hasColumn('garages', 'default_service_reminder_days')) {
                $missing[] = function ($table) { $table->integer('default_service_reminder_days')->default(90); };
            }
            if (!Schema::hasColumn('garages', 'google_review_url')) {
                $missing[] = function ($table) { $table->string('google_review_url', 500)->nullable(); };
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

        // Auto-ensure slug exists for this garage
        if ($garage && empty($garage->slug) && !empty($garage->name)) {
            $baseSlug = \Illuminate\Support\Str::slug($garage->name) ?: ('garage-' . $garage->id);
            $slug = $baseSlug;
            $count = 1;
            while (Garage::where('slug', $slug)->where('id', '!=', $garage->id)->exists()) {
                $slug = "{$baseSlug}-{$count}";
                $count++;
            }
            $garage->slug = $slug;
            $garage->save();
        }

        $bookingUrl = $garage && $garage->slug ? url('/book/' . $garage->slug) : url('/book');
        
        return Inertia::render('Settings/Edit', [
            'garage' => $garage,
            'bookingUrl' => $bookingUrl,
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

        if ($request->filled('slug')) {
            $request->merge([
                'slug' => \Illuminate\Support\Str::slug($request->slug)
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:60|regex:/^[a-z0-9\-]+$/|unique:garages,slug,' . $garage->id,
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
            'default_service_reminder_days' => 'nullable|integer|min:7|max:730',
            'google_review_url' => 'nullable|url|max:500',
        ], [
            'slug.regex' => 'The custom booking link slug may only contain lowercase letters, numbers, and dashes.',
            'slug.unique' => 'This custom booking link slug is already taken by another garage. Please choose a different one.',
            'google_review_url.url' => 'Please enter a valid URL (e.g. https://g.page/r/.../review or https://maps.app.goo.gl/...).',
        ]);

        try {
            $garage->name = $validated['name'];
            if (isset($validated['slug'])) {
                $garage->slug = !empty($validated['slug']) ? $validated['slug'] : (\Illuminate\Support\Str::slug($validated['name']) ?: ('garage-' . $garage->id));
            }
            if (isset($validated['default_service_reminder_days'])) {
                $garage->default_service_reminder_days = (int) $validated['default_service_reminder_days'];
            }
            if (array_key_exists('google_review_url', $validated)) {
                $garage->google_review_url = $validated['google_review_url'];
            }
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
                    \App\Services\GarageStorageService::delete($garage->logo_path);
                }
                $garage->logo_path = null;
            }

            // Handle uploading new logo
            if ($request->hasFile('logo')) {
                if ($garage->logo_path) {
                    \App\Services\GarageStorageService::delete($garage->logo_path);
                }
                $path = \App\Services\GarageStorageService::store($request->file('logo'), 'branding', $garage);
                $garage->logo_path = $path;
            }

            // Handle removing QR code
            if (!empty($validated['remove_qr_code'])) {
                if ($garage->qr_code_path) {
                    \App\Services\GarageStorageService::delete($garage->qr_code_path);
                }
                $garage->qr_code_path = null;
            }

            // Handle uploading new QR code
            if ($request->hasFile('qr_code')) {
                if ($garage->qr_code_path) {
                    \App\Services\GarageStorageService::delete($garage->qr_code_path);
                }
                $path = \App\Services\GarageStorageService::store($request->file('qr_code'), 'branding', $garage);
                $garage->qr_code_path = $path;
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

