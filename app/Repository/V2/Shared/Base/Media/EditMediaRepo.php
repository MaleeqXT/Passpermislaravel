<?php

namespace App\Repository\V2\Shared\Base\Media;

use App\Models\Media\Media;
use App\Models\Media\StorageMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EditMediaRepo
{
    /**
     * @param array $attributes
     * @param Model $model
     * @return void
     */
       public static function run(array $attributes, Model $model): void
    {
        if (! isset($attributes['media'])) {
            return;
        }

        /** @var UploadedFile $file */
        $file = $attributes['media'];

        $media = $model->media;
        $oldStorageId = $media?->storage_media_id;


  


        /*
        |--------------------------------------------------------------------------
        | Upload new file
        |--------------------------------------------------------------------------
        */

        $path = $file->store('media', 'public');

        $storageMedia = StorageMedia::create([
            'user_id'   => auth()->id(),
            'path'      => $path,
            'thumb'     => null,
            'name'      => $file->getClientOriginalName(),
            'type'      => $file->getClientMimeType(),
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update relation
        |--------------------------------------------------------------------------
        */

        if ($media) {

            $media->update([
                'storage_media_id' => $storageMedia->id,
                'user_id' => auth()->id(),
            ]);

        } else {

            $model->media()->create([
                'storage_media_id' => $storageMedia->id,
                'user_id' => auth()->id(),
            ]);
        }


                if ($media) {

            $usedSomewhereElse = Media::where('storage_media_id', $oldStorageId)
                ->where('id', '!=', $media->id)
                ->exists();

            if (! $usedSomewhereElse) {

                $oldStorage = StorageMedia::find($oldStorageId);

                if ($oldStorage) {

                    Storage::disk('public')->delete($oldStorage->path);

                    $oldStorage->delete();
                }
            }
        }

    }

}
