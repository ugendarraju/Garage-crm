<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCardItem extends Model
{
    use \App\Traits\BelongsToGarage;

    protected $guarded = [];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
