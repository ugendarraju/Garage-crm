<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    use \App\Traits\BelongsToGarage;

    /** @use HasFactory<\Database\Factories\JobCardFactory> */
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'job_number',
        'customer_id',
        'vehicle_id',
        'created_by',
        'status',
        'expected_delivery_date',
        'description',
        'advance_payment',
        'discount',
        'subtotal',
        'gst_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'final_total',
        'payment_status',
        'priority',
        'next_service_reminder',
        'vehicle_running_km',
        'fuel_range',
        'amount_paid',
        'payment_screenshot_path',
        'inspection_checklist',
        'service_reminder_sent_at',
    ];

    protected $casts = [
        'inspection_checklist' => 'array',
        'next_service_reminder' => 'date',
        'service_reminder_sent_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mechanics()
    {
        return $this->belongsToMany(User::class, 'job_card_mechanic', 'job_card_id', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(JobCardItem::class);
    }

    public function issues()
    {
        return $this->hasMany(JobCardIssue::class);
    }

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }
}
