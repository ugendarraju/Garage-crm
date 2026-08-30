<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'is_active',
        'ai_api_key',
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
