<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleBrand extends Model
{
    use \App\Traits\BelongsToGarage;
    protected $fillable = ['garage_id', 'name'];
    
    public function models()
    {
        return $this->hasMany(VehicleModel::class, 'brand_id');
    }
}
