<?php

namespace App\Repository\V2\Monitor\User;

use App\Models\Roles\Monitor\User\Monitor;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreMonitorRepo
{
    /**
     * @param User|string $user
     * @param array $attributes
     * @return Model|Builder
     */
    public static function run(User|string $user, array $attributes): Model|null
    {
        try {
            $user_id = $user instanceof Model ? $user->id : $user;
            $attributes['user_id'] = $user_id;

            $monitor = Monitor::query()->create(Arr::only($attributes, ['user_id','status']));
            $monitor->details()->create(Arr::only($attributes, ['experience', 'dernier_experience', 'zone_souhaitee', 'details_experience', 'is_manual', 'is_auto', 'departement', 'numero_autorisation', 'tarif_car', 'tarif_enseignement']));
            return $monitor;
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
