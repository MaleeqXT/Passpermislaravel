<?php

namespace Database\Factories\Roles\Student\Schedule;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\User\Wallet;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Training::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $student = Student::query()->inRandomOrder()->first();
        $reservation = Reservation::query()
            ->whereDoesntHave('training') // Ensure reservation is not already taken
            ->inRandomOrder()
            ->first();

        return [
            'offer_id' => Wallet::factory()->create(['student_id' => $student->id])->offer_id,
            'reservation_id' => $reservation?->id,
            'student_id' => $student->id,
        ];
    }
}
