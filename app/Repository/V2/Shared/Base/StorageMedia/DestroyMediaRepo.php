<?php

namespace App\Repository\V2\Shared\Base\StorageMedia;

use App\Models\Media\Media;
use App\Models\Media\StorageMedia;
use Illuminate\Support\Facades\File;

class DestroyMediaRepo
{
    /**
     * @param StorageMedia $storageMedia
     * @return void
     */
    public static function run(StorageMedia $storageMedia): void
    {
        $media = Media::query()->where('storage_media_id', $storageMedia?->id)->exists();

        if (isset($storageMedia?->path) && !$media) {
            File::delete(ltrim($storageMedia?->path, '/'));
        }
        if ($media) {
            StorageMedia::query()->where('id', $storageMedia?->id)->update(['is_active' => false]);
        } else {
            $storageMedia->delete();
        }
    }
}
