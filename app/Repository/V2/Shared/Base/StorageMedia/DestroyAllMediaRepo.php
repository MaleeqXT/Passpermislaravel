<?php

namespace App\Repository\V2\Shared\Base\StorageMedia;

use App\Models\Media\Media;
use App\Models\Media\StorageMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class DestroyAllMediaRepo
{
    /**
     * @param string|Model $model
     * @return void
     */
    public static function run(Model|string $model = User::class): void
    {

        foreach ($model?->storageMedia as $item) {
            $media = Media::query()->where('storage_media_id', $item?->id)->exists();

            if (isset($item?->path) && !$media) {
                File::delete(ltrim($item?->path, '/'));
            }
            if ($media) {
                StorageMedia::query()->where('id', $item?->id)->update(['is_active' => false]);
            } else {
                $item->delete();
            }
        }
    }
}
