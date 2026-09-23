<?php

namespace App\Repository\V2\Monitor\Instructor\Permission;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use App\Repository\V2\Shared\Base\Media\EditManyMediaRepo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditPermissionInstructorRepo
{
    /**
     * @param InstructorDocument $instructorDocument
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(InstructorDocument $instructorDocument, array $attributes = []): Model|Builder|null
    {
        try {
            $document = $instructorDocument->instructorPermission()->updateOrCreate(
                Arr::only($attributes, ['instructor_document_id']),
                Arr::except($attributes, ['media', 'instructor_document_id'])
            );

            EditManyMediaRepo::run(Arr::only($attributes, ['media']), $document);
            return $document;
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
