<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToGarage;

class Expense extends Model
{
    use HasFactory, BelongsToGarage;

    protected $fillable = [
        'garage_id',
        'category',
        'title',
        'amount',
        'payment_method',
        'expense_date',
        'receipt_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function categories(): array
    {
        return [
            'rent' => 'Workshop Rent',
            'utilities' => 'Electricity, Water & Internet',
            'food_tea' => 'Staff Tea & Refreshments',
            'consumables' => 'Shop Consumables (Sprays, Grease, Rags)',
            'tools' => 'Tools & Machinery Maintenance',
            'salary_advance' => 'Staff Advance & Wages',
            'maintenance' => 'Garage Repairs & Infrastructure',
            'other' => 'Miscellaneous / Other',
        ];
    }
}
