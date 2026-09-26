<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'user_id',
        'user_name',
        'user_role',
        'action',
        'module',
        'description',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForGarage($query, $garageId)
    {
        if ($garageId !== null) {
            return $query->where('garage_id', $garageId);
        }
        return $query;
    }

    public function scopeFilterModule($query, ?string $module)
    {
        if (!empty($module) && $module !== 'all') {
            return $query->where('module', $module);
        }
        return $query;
    }

    public function scopeFilterAction($query, ?string $action)
    {
        if (!empty($action) && $action !== 'all') {
            return $query->where('action', $action);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!empty($search)) {
            return $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}
