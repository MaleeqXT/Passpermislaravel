<?php

namespace App\Http\Resources\System\Params\Zip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZipResource extends JsonResource
{
    public static $wrap = 'zone';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'created_at' => $this->created_at
        ];
    }
}
