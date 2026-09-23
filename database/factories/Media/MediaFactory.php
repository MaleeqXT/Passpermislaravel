<?php

namespace Database\Factories\Media;

use App\Models\Media\StorageMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //store the file in the storage_path and return the path
            'user_id' => User::query()->inRandomOrder()->first()?->id ?? User::factory()->create()?->id,
            'storage_media_id' => StorageMedia::query()->inRandomOrder()->first()?->id ?? StorageMedia::factory()->create()?->id,
        ];
    }
}
