<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'address',
        'phone',
        'email',
        'is_active',
        'ai_api_key',
        'logo_path',
        'header_logo_size',
        'gst_number',
        'bank_details',
        'upi_id',
        'qr_code_path',
        'default_service_reminder_days',
        'google_review_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Prevents leaking privileged third-party keys client-side.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'ai_api_key',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function owner()
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    public function owners()
    {
        return $this->hasMany(User::class)->where('role', 'owner');
    }

    public static function getActiveGarageId()
    {
        if (session()->has('active_garage_id')) {
            return session('active_garage_id');
        }
        
        if (auth()->check() && auth()->user()->garage_id) {
            return auth()->user()->garage_id;
        }

        return null;
    }

    public function storagePath(string $subfolder = ''): string
    {
        return \App\Services\GarageStorageService::getGarageFolder($this) . ($subfolder ? '/' . trim($subfolder, '/') : '');
    }

    public static function activeStoragePath(string $subfolder = ''): string
    {
        return \App\Services\GarageStorageService::getGarageFolder(null) . ($subfolder ? '/' . trim($subfolder, '/') : '');
    }
}
