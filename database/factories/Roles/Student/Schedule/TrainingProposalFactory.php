<?php

namespace database\factories\Roles\Student\Schedule;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingProposal>
 */
class TrainingProposalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = TrainingProposal::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::query()->inRandomOrder()->whereDoesntHave('training')->first()->id,
            'student_id' => Student::query()->inRandomOrder()->first()->id,
            'status' => $this->faker->randomElement([1, 2, 3]),
            'comment' => $this->faker->text(),
        ];
    }
}
