<?php

namespace database\factories\Roles\Admin\Area;

use App\Models\Roles\Admin\Area\Zip;
use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Admin\Area\Zip>
 */
class ZipFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Zip::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone_id' => \App\Models\Roles\Admin\Area\Zone::query()->inRandomOrder()->first()->id,
            'code' => fake()->postcode(),
        ];
    }
}
