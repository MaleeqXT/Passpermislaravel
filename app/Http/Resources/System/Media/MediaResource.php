<?php

namespace App\Http\Resources\System\Media;

use App\Http\Resources\System\StorageMedia\StorageMediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public static $wrap = 'media';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'storage' =>StorageMediaResource::make($this->whenLoaded('storageMedia')),
            'user' => $this->whenLoaded('user')
        ];
    }
}
