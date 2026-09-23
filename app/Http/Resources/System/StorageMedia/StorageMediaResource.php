<?php

namespace App\Http\Resources\System\StorageMedia;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageMediaResource extends JsonResource
{
    public static $wrap = 'storageMedia';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'type' => $this->type,
            'thumb' => $this->thumb,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'user' => $this->whenLoaded('user')
        ];
    }
}
