<?php

namespace database\factories\Roles\Student\User\Competency;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competency>
 */
class CompetencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Competency::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->word,
            'status' => $this->faker->randomElement([SituationStatusEnum::ACTIVE->value, SituationStatusEnum::INACTIVE->value]),
            'main_competency_id' => MainCompetency::query()->inRandomOrder()->first()->id,
        ];
    }
}
