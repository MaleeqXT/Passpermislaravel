<?php

namespace database\factories\Roles\Monitor\User\Informations;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Informations\MonitorInformation;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorInformation>
 */
class MonitorInformationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = MonitorInformation::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::query()->inRandomOrder()->first()?->id ?? Monitor::factory()->create()?->id,
            'experience' => $this->faker->numberBetween(0, 10),
            'dernier_experience' => $this->faker->numberBetween(0, 10),
            'details_experience' => $this->faker->text(200),
        ];
    }
}
