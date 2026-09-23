<?php

namespace App\Repository\V2\Shared\Base\StorageMedia;

use App\Models\Media\StorageMedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FetchAllMediaRepo
{
    /**
     * @param array|null $attributes
     * @return LengthAwarePaginator
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        return StorageMedia::query()
            ->when(isset($attributes['type']), function ($query) use ($attributes) {
                return $query->whereIn('type', $attributes['type']);
            })
            ->when(isset($attributes['name']), function ($query) use ($attributes) {
                return $query->where('name', 'like', '%' . $attributes['name'] . '%');
            })
            ->where('is_active', true)
            ->where('user_id', auth()->id())
            ->orderBy('created_at', isset($attributes['sort']) ? $attributes['sort'] : 'desc')
            ->paginate();
    }
}
