<?php

namespace App\Repository\V2\Monitor\Instructor\Permission;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FetchInstructorPermissionRepo
{
    /**
     * @param InstructorDocument $instructorDocument
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(InstructorDocument $instructorDocument, array $attributes = null): Collection|Builder
    {
        return InstructorPermission::query()
            ->where('instructor_document_id', $instructorDocument)
            ->first();
    }
}
