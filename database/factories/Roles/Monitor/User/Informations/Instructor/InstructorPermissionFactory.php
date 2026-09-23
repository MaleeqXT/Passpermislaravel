<?php

namespace database\factories\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorPermission>
 */
class InstructorPermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = InstructorPermission::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'autorisation' => fake()->date,
            'visite' => fake()->date,
            'instructor_document_id' => InstructorDocument::query()->inRandomOrder()->first()->id
        ];
    }
}
