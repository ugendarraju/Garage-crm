<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SystemSettingService
{
    protected static string $settingsFile = 'system_settings.json';

    /**
     * Default settings
     */
    protected static array $defaults = [
        'crm_name' => 'PistonCRM',
        'crm_subheading' => 'Smart Workspace',
        'crm_logo_path' => null,
        'crm_logo_size' => 36,
        'crm_favicon_path' => null,
    ];

    /**
     * Get all settings merged with defaults
     */
    public static function getAll(): array
    {
        $settings = self::$defaults;

        try {
            if (Storage::disk('local')->exists(self::$settingsFile)) {
                $fileContent = Storage::disk('local')->get(self::$settingsFile);
                $decoded = json_decode($fileContent, true);
                if (is_array($decoded)) {
                    $settings = array_merge($settings, $decoded);
                }
            }
        } catch (\Throwable $e) {
            // Silently fall back to defaults
        }

        return $settings;
    }

    /**
     * Get a specific setting value
     */
    public static function get(string $key, $default = null)
    {
        $all = self::getAll();
        return $all[$key] ?? ($default ?? (self::$defaults[$key] ?? null));
    }

    /**
     * Set multiple settings
     */
    public static function setMany(array $data): bool
    {
        try {
            $all = self::getAll();
            $updated = array_merge($all, $data);
            Storage::disk('local')->put(self::$settingsFile, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Set a single setting
     */
    public static function set(string $key, $value): bool
    {
        return self::setMany([$key => $value]);
    }

    /**
     * Get the full URL of the uploaded logo, or null if none
     */
    public static function getLogoUrl(): ?string
    {
        $path = self::get('crm_logo_path');
        if (!$path) {
            return null;
        }

        // If it's already an absolute URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Get the full URL of the uploaded favicon, or null if none
     */
    public static function getFaviconUrl(): ?string
    {
        $path = self::get('crm_favicon_path');
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
