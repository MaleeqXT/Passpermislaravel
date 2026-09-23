<?php

namespace App\Http\Resources\System\Params\Zip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ZipCollection extends ResourceCollection
{
    public $collects = ZipResource::class;
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
