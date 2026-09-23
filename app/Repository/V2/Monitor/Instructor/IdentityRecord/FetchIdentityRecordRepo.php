<?php

namespace App\Repository\V2\Monitor\Instructor\IdentityRecord;

use App\Models\Roles\Monitor\User\Informations\Instructor\IdentityRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FetchIdentityRecordRepo
{
    /**
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes = null)
    {
        return IdentityRecord::query()
            ->where('monitor_id', getMonitorId($attributes))
            ->first();
    }


}
