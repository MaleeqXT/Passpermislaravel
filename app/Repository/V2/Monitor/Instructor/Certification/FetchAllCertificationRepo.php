<?php

namespace App\Repository\V2\Monitor\Instructor\Certification;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorCertification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FetchAllCertificationRepo
{
    /**
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes = null)
    {
        return InstructorCertification::query()
            ->where('monitor_id', getMonitorId($attributes))
            ->first();
    }


}
