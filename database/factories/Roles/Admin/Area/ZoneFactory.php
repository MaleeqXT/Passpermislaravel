<?php

namespace database\factories\Roles\Admin\Area;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Admin\Area\Zone>
 */
class ZoneFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Zone::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city
        ];
    }
}
