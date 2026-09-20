<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    protected $fillable = [
        'name',
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
    ];

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
        
        if (auth()->check() && auth()->user()->role === 'owner') {
            return auth()->user()->garage_id;
        }

        return null;
    }
}
