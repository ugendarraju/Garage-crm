<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SystemSettingsController extends Controller
{
    /**
     * Display the system settings editing form.
     */
    public function edit()
    {
        // Enforce super_admin role
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $settings = SystemSettingService::getAll();
        $settings['logo_url'] = SystemSettingService::getLogoUrl();

        return Inertia::render('SuperAdmin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the system settings.
     */
    public function update(Request $request)
    {
        // Enforce super_admin role
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'crm_name' => 'required|string|max:100',
            'crm_subheading' => 'nullable|string|max:150',
            'crm_logo_size' => 'nullable|integer|min:20|max:80',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        $settingsData = [
            'crm_name' => trim($validated['crm_name']),
            'crm_subheading' => isset($validated['crm_subheading']) ? trim($validated['crm_subheading']) : '',
            'crm_logo_size' => (int) ($validated['crm_logo_size'] ?? 36),
        ];

        // Handle removing logo
        if (!empty($validated['remove_logo'])) {
            $currentLogo = SystemSettingService::get('crm_logo_path');
            if ($currentLogo) {
                Storage::disk('public')->delete($currentLogo);
            }
            $settingsData['crm_logo_path'] = null;
        }

        // Handle file upload
        if ($request->hasFile('logo')) {
            $currentLogo = SystemSettingService::get('crm_logo_path');
            if ($currentLogo) {
                Storage::disk('public')->delete($currentLogo);
            }

            $path = $request->file('logo')->store('branding', 'public');
            $settingsData['crm_logo_path'] = $path;

            // Also copy to public/storage/branding if possible to guarantee web accessibility
            try {
                $destination = public_path('storage/' . $path);
                $destDir = dirname($destination);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                @copy(Storage::disk('public')->path($path), $destination);
            } catch (\Throwable $e) {
                // Ignore copy errors
            }
        }

        SystemSettingService::setMany($settingsData);

        return redirect()->back()->with('success', 'CRM branding settings updated successfully!');
    }
}
