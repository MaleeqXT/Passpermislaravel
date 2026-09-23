<?php

namespace database\factories\Roles\Monitor\User\Informations\Instructor\Car;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Informations\Instructor\Car\CarAssurance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Monitor\User\Informations\Instructor\Car\CarAssurance>
 */
class CarAssuranceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CarAssurance::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_id' => \App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car::query()->inRandomOrder()->first()?->id,
        ];
    }
}
