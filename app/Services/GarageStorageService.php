<?php

namespace App\Services;

use App\Models\Garage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GarageStorageService
{
    /**
     * Get the root folder for a specific garage based on its slug or name.
     * Example: garages/demo-auto-shop
     */
    public static function getGarageFolder(Garage|int|null $garage = null): string
    {
        if (is_numeric($garage)) {
            $garage = Garage::find($garage);
        } elseif (!$garage) {
            $activeId = Garage::getActiveGarageId();
            $garage = $activeId ? Garage::find($activeId) : null;
        }

        if (!$garage) {
            return 'garages/shared';
        }

        $folderName = $garage->slug ?: Str::slug($garage->name ?: 'garage-' . $garage->id);

        return "garages/{$folderName}";
    }

    /**
     * Store an uploaded file inside the garage's dedicated subfolder.
     *
     * Subfolders:
     * - 'job_cards'       (Photos & inspection media)
     * - 'bills'           (Payment screenshots & customer receipts)
     * - 'service_requests'(Booking request issue photos)
     * - 'purchases'       (Supplier bills & payment receipts)
     * - 'expenses'        (Daily operational bill photos & receipts)
     * - 'branding'        (Workshop logo & UPI QR code)
     */
    public static function store(UploadedFile $file, string $subfolder, Garage|int|null $garage = null): string
    {
        $baseFolder = self::getGarageFolder($garage);
        $targetDirectory = trim($baseFolder, '/') . '/' . trim($subfolder, '/');

        $path = $file->store($targetDirectory, 'public');

        // Resilience copy for Hostinger / cPanel shared hosting without symlinks
        try {
            $destination = public_path('storage/' . $path);
            $destDir = dirname($destination);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            if (Storage::disk('public')->exists($path)) {
                @copy(Storage::disk('public')->path($path), $destination);
            }
        } catch (\Throwable $e) {}

        return $path;
    }

    /**
     * Safely delete an existing file from disk and public storage.
     */
    public static function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        $cleanPath = ltrim(str_replace('/storage/', '', $path), '/');

        try {
            Storage::disk('public')->delete($cleanPath);
            @unlink(public_path('storage/' . $cleanPath));
        } catch (\Throwable $e) {}
    }
}
