<?php

namespace App\Repository\V2\Monitor\Billing;

use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Models\Roles\Monitor\User\Monitor;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditInvoiceRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Monitor $monitor, array $attributes = []): Model|Builder|null
    {
        try {
            return Billing::query()->updateOrCreate(
                Arr::only($attributes, ['monitor_id', 'from', 'to']),
                Arr::except($attributes, ['from', 'to'])
            );
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create Or Update : ' . $e->getMessage());
            return null;
        }

    }
}
