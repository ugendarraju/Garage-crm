<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToGarage
{
    protected static function bootBelongsToGarage()
    {
        static::addGlobalScope('garage', function (Builder $builder) {
            $garageId = self::getActiveGarageId();
            
            if ($garageId) {
                $builder->where(function ($query) use ($garageId) {
                    $query->where('garage_id', $garageId)
                          ->orWhereNull('garage_id');
                });
            }
        });

        static::creating(function ($model) {
            $garageId = self::getActiveGarageId();
            if ($garageId) {
                $model->garage_id = $garageId;
            }
        });
    }

    protected static function getActiveGarageId()
    {
        if (!auth()->check()) {
            return null;
        }

        $user = auth()->user();

        if ($user->role === 'super_admin') {
            return session('active_garage_id');
        }

        return $user->garage_id;
    }
}
