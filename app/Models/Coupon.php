<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory, \App\Traits\BelongsToGarage;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'expiry_date' => 'date',
        'discount_value' => 'decimal:2',
    ];

    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }
        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return 'Expired';
        }
        return 'Active';
    }

    public function calculateDiscount($total)
    {
        if ($this->discount_type === 'percentage') {
            return ($total * $this->discount_value) / 100;
        }
        
        return min($this->discount_value, $total);
    }
}
