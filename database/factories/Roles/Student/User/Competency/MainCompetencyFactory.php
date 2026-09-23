<?php

namespace database\factories\Roles\Student\User\Competency;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MainCompetency>
 */
class MainCompetencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = MainCompetency::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'label' => $this->faker->word,
            'position' => $this->faker->numberBetween(1, 10),
        ];
    }
}
