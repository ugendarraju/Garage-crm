<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToGarage
{
    protected static function bootBelongsToGarage()
    {
        static::addGlobalScope('garage', function (Builder $builder) {
            $garageId = self::getActiveGarageId();
            $model = $builder->getModel();
            $allowsGlobal = method_exists($model, 'allowsGlobalGarage') && $model->allowsGlobalGarage();
            
            if ($garageId) {
                if ($allowsGlobal) {
                    $builder->where(function ($query) use ($garageId) {
                        $query->where('garage_id', $garageId)
                              ->orWhereNull('garage_id');
                    });
                } else {
                    $builder->where('garage_id', $garageId);
                }
            } elseif (auth()->check() && auth()->user()->role !== 'super_admin') {
                if (!$allowsGlobal) {
                    $builder->whereRaw('1 = 0');
                }
            }
        });

        static::creating(function ($model) {
            $garageId = self::getActiveGarageId();
            if ($garageId && empty($model->garage_id)) {
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
