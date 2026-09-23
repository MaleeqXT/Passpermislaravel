<?php

namespace database\factories\Roles\Monitor\Schedule;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewMonitor>
 */
class ReviewMonitorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ReviewMonitor::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comment' => $this->faker->text(),
            'estimation' => $this->faker->numberBetween(1, 25),
            'reservation_id' => Reservation::query()->whereHas('training')->whereDoesntHave('reviewMonitor')->inRandomOrder()->first()?->id,
            //   'monitor_id' => Monitor::query()->inRandomOrder()->first()->id
        ];
    }
}
