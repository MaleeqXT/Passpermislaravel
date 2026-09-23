<?php

namespace App\Repository\V2\Shared\Base\Media;

use Illuminate\Database\Eloquent\Model;

class StoreManyMediaRepo
{
    /**
     * @param array $attributes
     * @param Model $model
     * @return void
     */
    public static function run(array $attributes, Model $model): void
    {

        if (isset($attributes['media'])) {
            foreach ($attributes['media'] as $media) {
                $model->media()->create([
                    'storage_media_id' => $media,
                    'user_id' => auth()->id(),
                ]);
            }
        }
    }
}
