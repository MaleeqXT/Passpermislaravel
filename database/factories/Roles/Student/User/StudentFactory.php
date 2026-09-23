<?php

namespace database\factories\Roles\Student\User;

use App\Enums\V2\Student\User\StudentSituationStatusEnum;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory
 */
class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Student::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::query()->inRandomOrder()->whereFirstName('Soufiyan')->first()?->id ?? User::factory()->create()?->id,
            'balance' => $this->faker->numberBetween(0, 40),
            'neph' => $this->faker->numberBetween(0, 40),
            'is_cpf' => $this->faker->boolean,
            'date_code' => $this->faker->date(),
            'how_know' => $this->faker->word,
            'status' => Arr::random([StudentSituationStatusEnum::ACTIVE->value, StudentSituationStatusEnum::INACTIVE->value]),
        ];
    }
}
