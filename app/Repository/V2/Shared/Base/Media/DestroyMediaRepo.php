<?php

namespace App\Repository\V2\Shared\Base\Media;

use App\Models\Media\Media;
use App\Models\Media\StorageMedia;
use Illuminate\Support\Facades\File;
use Throwable;

class DestroyMediaRepo
{
    /**
     * @param Media $media
     * @return bool|null
     * @throws Throwable
     */
    public static function run(Media $media): ?bool
    {
        $doubleCheckMedia = Media::query()->where('storage_media_id', $media?->storage_media_id)
            ->whereNot('id', $media->id)->exists();
        if (empty($doubleCheckMedia)) {
            $storageMedia = StorageMedia::query()->where('is_active', false)->where('id', $media->storage_media_id)->first();

            if (isset($storageMedia)) {
                if (isset($storageMedia?->path)) {
                    File::delete(ltrim($storageMedia?->path, '/'));
                }
                $storageMedia->delete();
            }
        }

        return $media->deleteOrFail();
    }
}
