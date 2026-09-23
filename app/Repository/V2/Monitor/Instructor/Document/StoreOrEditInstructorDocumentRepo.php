<?php

namespace App\Repository\V2\Monitor\Instructor\Document;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use App\Models\Roles\Monitor\User\Monitor;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditInstructorDocumentRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return Model|Builder
     */
    public static function run(Monitor $monitor, array $attributes = []): Model|Builder|null
    {
        try {
            return InstructorDocument::query()->updateOrCreate(
                ['monitor_id'=>$monitor->id],
                Arr::except($attributes, ['monitor_id'])
            );
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update or Create : ' . $e->getMessage());
            return null;
        }

    }
}
