<?php

namespace database\factories\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorDocument>
 */
class InstructorDocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = InstructorDocument::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::query()->inRandomOrder()->first()->id,
            'denomination_social' => fake()->name,
            'forme_juridique' => fake()->name,
            'date_creation' => fake()->date,
            'siret' => fake()->creditCardNumber,
            'num_autorisation' => fake()->creditCardNumber,
        ];
    }
}
