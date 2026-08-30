<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use \App\Traits\BelongsToGarage;

    protected $fillable = [
        'garage_id',
        'customer_name',
        'email',
        'phone',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_year',
        'issues',
        'status',
        'vehicle_running_km',
        'fuel_range',
    ];
}
