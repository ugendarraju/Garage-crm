<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToGarage;

class PartBrand extends Model
{
    use BelongsToGarage;
    protected $guarded = [];
}
