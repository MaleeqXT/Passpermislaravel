<?php

namespace App\Repository\V2\Monitor\Instructor\Document;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FetchInstructorDocumentRepo
{
    /**
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes = null): Builder|Model|null
    {
        return InstructorDocument::query()
            ->with('instructorPermission')
            ->where('monitor_id', getMonitorId($attributes))
            ->first();
    }
}
