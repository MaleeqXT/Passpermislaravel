<?php

namespace App\Http\Resources\System\Params\Zone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ZoneCollection extends ResourceCollection
{
    public $collects = ZoneResource::class;
    public static $wrap = 'data';

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
