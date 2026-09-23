<?php

namespace App\Http\Resources\System\StorageMedia;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StorageMediaCollection extends ResourceCollection
{
    public $collects = StorageMediaResource::class;
    public static $wrap = 'storageMedia';

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
