<?php

namespace database\factories\Roles\Student\Exam;

use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Exam\StudentExam;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @extends Factory<StudentExam>
 */
class StudentExamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = StudentExam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::query()->inRandomOrder()->first()->id,
            'user_id' => User::query()->first()->id,
            'monitor_id' => Monitor::query()->inRandomOrder()->first()->id,
            'lieu_id' => Lieu::query()->inRandomOrder()->first()->id,
            'comment' => fake()->text,
            'status' => Arr::random([
                ExamenStatusEnum::SUCCESS->value,
                ExamenStatusEnum::FAILED->value,
                ExamenStatusEnum::PENDING->value,
            ]),
            'date_examen' => fake()->date,
            'heure_passage' => fake()->time,
            'result_permis' => fake()->text,
        ];
    }
}
