<?php

namespace App\Repository\V2\Monitor\Instructor\Information;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorAccount;
use App\Models\Roles\Monitor\User\Monitor;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditInstructorAccountRepo
{
    /**
     * @return Model|Builder
     */

   
    // public static function run(Monitor $monitor, array $attributes = []): Model|Builder|null
    // {

    //     try {
    //         return InstructorAccount::query()->updateOrCreate(
    //             Arr::only($attributes, ['monitor_id']),
    //             Arr::only($attributes, ['iban']),
    //             Arr::only($attributes, ['bic']),
    //             Arr::except($attributes, ['monitor_id'])
    //         );
    //     } catch (Exception $e) {
    //         // Log the exception message for debugging purposes
    //         info('Failed to Create : ' . $e->getMessage());
    //         return null;
    //     }

    // }

    public static function run(Monitor $monitor, array $attributes = []): Model|Builder|null
{
    try {
        return InstructorAccount::query()->updateOrCreate(
            ['monitor_id' => $monitor->id],          // search condition - $monitor parameter se
            Arr::only($attributes, ['iban', 'bic'])   // values - request data se
        );
    } catch (Exception $e) {
        info('Failed to Create : ' . $e->getMessage());
        return null;
    }
}
}
