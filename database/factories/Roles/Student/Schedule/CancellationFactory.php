<?php

namespace database\factories\Roles\Student\Schedule;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Student\Schedule\Cancellation;
use App\Models\Roles\Student\Schedule\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cancellation>
 */
class CancellationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Cancellation::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::query()->inRandomOrder()->first()->id,
            'is_justified' => fake()->boolean(30),
            'comment' => fake()->name,
        ];
    }
}
