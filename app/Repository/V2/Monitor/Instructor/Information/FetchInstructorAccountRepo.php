<?php

namespace App\Repository\V2\Monitor\Instructor\Information;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FetchInstructorAccountRepo
{

    /**
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes = null) //: Collection|Builder
    {
        return InstructorAccount::query()
            ->where('monitor_id', getMonitorId($attributes))
            ->first();
    }


}
