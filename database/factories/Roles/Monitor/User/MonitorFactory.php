<?php

namespace database\factories\Roles\Monitor\User;

use App\Enums\V2\Monitor\MonitorSituationStatusEnum;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Monitor::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::query()->inRandomOrder()->whereName('monitor')->first()?->id ?? User::factory()->create()?->id,
            'status' => Arr::random([MonitorSituationStatusEnum::ACTIVE->value, MonitorSituationStatusEnum::INACTIVE->value]),
        ];
    }
}
