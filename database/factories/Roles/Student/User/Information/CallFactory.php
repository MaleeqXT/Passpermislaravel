<?php

namespace database\factories\Roles\Student\User\Information;

use App\Models\Roles\Student\User\Information\Call;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Call>
 */
class CallFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Call::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'user_id' => User::factory(),
            'date' => $this->faker->date(),
            'time_at' => $this->faker->time(),
        ];
    }
}
