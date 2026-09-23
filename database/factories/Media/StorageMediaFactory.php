<?php

namespace Database\Factories\Media;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends Factory
 */
class StorageMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::query()->inRandomOrder()->first()?->id ?? User::factory()->create()?->id;

        // Create the storage directory if it doesn't exist
        if (!File::exists(storage_path('app/public/' . $user))) {
            File::makeDirectory(storage_path('app/public/' . $user), 0755, true);
        }
        return [
            //store the file in the storage_path and return the path
            'path' => 'https://robohash.org/' . fake()->uuid . '?size=300x300',
            'thumb' => 'https://robohash.org/' . fake()->uuid . '?size=100x100',
            'user_id' => $user,
            'name' => $this->faker->name(),
            'type' => 'png'
        ];
    }
}
