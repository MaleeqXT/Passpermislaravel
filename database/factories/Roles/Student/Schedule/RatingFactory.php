<?php

namespace database\factories\Roles\Student\Schedule;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\Rating;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Rating::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competency_id' => Competency::query()->whereDoesntHave('rating')->inRandomOrder()->first()->id,
            'comment' => $this->faker->sentence,
            'rating' => $this->faker->numberBetween(1, 3),
            'student_id' => Student::query()->inRandomOrder()->whereDoesntHave('ratings')->first()->id,
            'monitor_id' => Monitor::query()->whereDoesntHave('ratings')->inRandomOrder()->first()->id,
        ];
    }
}
