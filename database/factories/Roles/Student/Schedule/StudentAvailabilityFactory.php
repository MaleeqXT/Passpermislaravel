<?php

namespace database\factories\Roles\Student\Schedule;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Student\Schedule\StudentAvailability;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Student\Schedule\StudentAvailability>
 */
class StudentAvailabilityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = StudentAvailability::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::query()->inRandomOrder()->first()->id,
            'time_at' => $this->faker->time(),
            'date' => $this->faker->date(),
            'user_id' => User::query()->inRandomOrder()->first()->id,
        ];
    }
}
