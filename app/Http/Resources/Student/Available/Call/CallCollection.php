<?php

namespace App\Http\Resources\Student\Available\Call;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CallCollection extends ResourceCollection
{
    public static $wrap = 'availableCalls';
    public $collects = CallResource::class;

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
