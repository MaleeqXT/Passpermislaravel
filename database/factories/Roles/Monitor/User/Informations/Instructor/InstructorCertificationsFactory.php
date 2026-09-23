<?php

namespace database\factories\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorCertification;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Monitor\User\Informations\Instructor\InstructorCertification>
 */
class InstructorCertificationsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = InstructorCertification::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::query()->inRandomOrder()->first()->id,

        ];
    }
}
