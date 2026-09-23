<?php

namespace database\factories\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorAccount;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorAccount>
 */
class InstructorAccountFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = InstructorAccount::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::query()->whereDoesntHave('account')->inRandomOrder()->first()->id,
            'iban' => $this->faker->iban('FR'),
            'bic' => $this->faker->swiftBicNumber,
        ];
    }
}
