<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    use \App\Traits\BelongsToGarage;

    protected $fillable = ['brand_id', 'name', 'garage_id'];
    
    public function brand()
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function allowsGlobalGarage(): bool
    {
        return true;
    }
}
