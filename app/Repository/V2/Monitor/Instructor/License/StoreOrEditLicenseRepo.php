<?php

namespace App\Repository\V2\Monitor\Instructor\License;

use App\Models\Roles\Monitor\User\Informations\Instructor\DriverLicense;
use App\Models\Roles\Monitor\User\Monitor;
use App\Repository\V2\Shared\Base\Media\EditManyMediaRepo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditLicenseRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Monitor $monitor, array $attributes = []): Model|Builder|null
    {

        try {
            $permis = DriverLicense::query()->updateOrCreate(
                ['monitor_id'=>$monitor->id]
            );
            EditManyMediaRepo::run(['media' => $attributes['media_permis'] ?? []], $permis);
            return $permis;
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
