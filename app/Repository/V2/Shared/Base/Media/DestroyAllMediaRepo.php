<?php

namespace App\Repository\V2\Shared\Base\Media;

use App\Models\Media\Media;
use App\Models\Media\StorageMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class DestroyAllMediaRepo
{
    /**
     * @param Model $model
     * @return void
     */
    public static function run(Model $model): void
    {

        foreach ($model?->media as $item) {
            $doubleCheckMedia = Media::query()->where('storage_media_id', $item?->storage_media_id)
                ->whereNot('id', $item?->id)->first();

            if (empty($doubleCheckMedia)) {
                $storageMedia = StorageMedia::query()->where('is_active', false)
                    ->where('id', $item?->storage_media_id)->first();

                if (isset($storageMedia)) {
                    if (isset($storageMedia?->path)) {
                        File::delete(ltrim($storageMedia?->path, '/'));
                    }
                    $storageMedia->delete();
                }
            }

            Media::query()->where('id', $item?->id)->delete();
        }
    }
}
