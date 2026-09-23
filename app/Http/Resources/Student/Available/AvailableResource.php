<?php

namespace App\Http\Resources\Student\Available;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableResource extends JsonResource
{
    public static $wrap = 'availables';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student' => $this->whenLoaded('student'),
            'user' => $this->whenLoaded('user'),
            'date' => $this->date,
            'time_at' => $this->time_at,
        ];
    }
}
