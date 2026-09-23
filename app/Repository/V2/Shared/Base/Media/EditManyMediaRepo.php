<?php

namespace App\Repository\V2\Shared\Base\Media;

use Illuminate\Database\Eloquent\Model;

class EditManyMediaRepo
{
    /**
     * @param array $attributes
     * @param Model $model
     * @return void
     */
    public static function run(array $attributes, Model $model): void
    {
        if (isset($attributes['media'])) {
            foreach ($attributes['media'] as $id) {
                $model->media()->updateOrCreate([
                    'storage_media_id' => $id,
                ], [
                    'user_id' => auth()->id(),
                ]);
            }
        }
    }
}
