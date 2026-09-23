<?php

namespace App\Repository\V2\Shared\Base\Media;

use App\Models\Media\Media;
use App\Models\Media\StorageMedia;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
class DestroyModelMediaRepo
{
    /**
     * @param Model $model
     * @return void
     */
    public static function run(Model $model): void
    {

        $item = $model?->media;
        $doubleCheckMedia = Media::query()->where('storage_media_id', $item?->storage_media_id)
            ->whereNot('id', $item?->id)->first();

        if (empty($doubleCheckMedia)) {
            // $storageMedia = StorageMedia::query()->where('is_active', false)->where('id', $item?->storage_media_id)->first();
            $storageMedia = StorageMedia::find($item?->storage_media_id);

            if (isset($storageMedia)) {
                if (isset($storageMedia?->path)) {
                    // File::delete(ltrim($storageMedia?->path, '/'));
                     Storage::disk('public')->delete($storageMedia->path);
                }
                $storageMedia->delete();
            }
        }

        Media::query()->where('id', $item?->id)->delete();
    }
}
