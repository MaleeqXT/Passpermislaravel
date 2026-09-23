<?php

namespace App\Repository\V2\Shared\Base\StorageMedia;

use App\Models\Media\StorageMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchMediaRepo
{
    /**
     * @param string $media_id
     * @return Builder|Model
     */
    public static function run(string $media_id,): Builder|Model
    {

        return StorageMedia::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->where('id', $media_id)
            ->firstOrFail();
    }
}
