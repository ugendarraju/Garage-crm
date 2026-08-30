<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_card_id',
        'description',
        'status',
        'mechanic_notes',
        'reported_by',
        'requires_consent',
        'consent_status',
        'images',
        'customer_notes',
    ];

    protected $casts = [
        'requires_consent' => 'boolean',
        'images' => 'array',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }
}
