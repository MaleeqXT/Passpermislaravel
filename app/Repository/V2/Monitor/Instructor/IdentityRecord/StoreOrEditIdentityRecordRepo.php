<?php

namespace App\Repository\V2\Monitor\Instructor\IdentityRecord;

use App\Models\Roles\Monitor\User\Informations\Instructor\IdentityRecord;
use App\Models\Roles\Monitor\User\Monitor;
use App\Repository\V2\Shared\Base\Media\EditManyMediaRepo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditIdentityRecordRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Monitor $monitor, array $attributes = []): Model|Builder|null
    {
        try {
            $piece = IdentityRecord::query()->updateOrCreate(['monitor_id'=>$monitor->id]);

            EditManyMediaRepo::run(['media' => $attributes['media_piece_identite'] ?? []], $piece);

            return $piece;
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
