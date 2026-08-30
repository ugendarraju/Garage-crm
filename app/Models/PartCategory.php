<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartCategory extends Model
{
    use \App\Traits\BelongsToGarage;
    use HasFactory;

    protected $guarded = [];
}
