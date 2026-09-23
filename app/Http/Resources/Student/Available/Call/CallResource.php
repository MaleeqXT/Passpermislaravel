<?php

namespace App\Http\Resources\Student\Available\Call;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallResource extends JsonResource
{
    public static $wrap = 'availableCall';

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
