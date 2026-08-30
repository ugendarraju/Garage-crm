<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'name',
        'description',
        'is_active',
    ];

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }
}
