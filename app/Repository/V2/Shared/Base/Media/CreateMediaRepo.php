<?php

namespace App\Repository\V2\Shared\Base\Media;

use Illuminate\Database\Eloquent\Model;
use App\Models\Media\StorageMedia;

use Illuminate\Http\UploadedFile;

class CreateMediaRepo
{
    /**
     * @param array $attributes
     * @param Model $model
     * @return void
     */
    public static function run(array $attributes, Model $model): void
    {
        // dd($model, $attributes);
          if (! isset($attributes['media'])) {
            return;
        }
          $file = $attributes['media'];

       
        $path = $file->store('media', 'public');
          $storageMedia = StorageMedia::create([
            'user_id'   => auth()->id(),
            'path'      => $path,
            'thumb'     => null,
            'name'      => $file->getClientOriginalName(),
            'type'      => $file->getClientMimeType(),
            'is_active' => true,
        ]);

        if (isset($attributes['media'])) {
            $model->media()->create([
              'storage_media_id' => $storageMedia->id,
                'user_id' => auth()->id(),
            ]);
        }
    }
}
