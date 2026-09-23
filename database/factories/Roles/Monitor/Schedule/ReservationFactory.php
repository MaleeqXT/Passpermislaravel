<?php

namespace Database\Factories\Roles\Monitor\Schedule;

use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $monitor = Monitor::query()->inRandomOrder()->first();
        $startDate = Carbon::now()->subDays(20)->startOfDay();
        $endDate = Carbon::now()->addDays(10)->endOfDay();

        do {
            $date = $this->faker->dateTimeBetween($startDate, $endDate)->format('Y-m-d');
            $start_at = $this->faker->numberBetween(8, 18) . ':00:00';
        } while (
            Reservation::where('monitor_id', $monitor->id)
            ->where('date', $date)
            ->where('start_at', $start_at)
            ->exists()
        );

        $hour = $this->faker->numberBetween(1, 2);
        return [
            'monitor_id' => $monitor->id,
            'date' => $date,
            'start_at' => $start_at,
            'end_at' => Carbon::parse($start_at)->addHours($hour)->format('H:i:s'),
            'hour' => $hour,
            'is_active' => 1,
            'lieu_id' => Lieu::query()->inRandomOrder()->first()?->id,
            'color' => $this->faker->hexColor,
        ];
    }
}
