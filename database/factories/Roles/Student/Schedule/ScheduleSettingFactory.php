<?php

namespace database\factories\Roles\Student\Schedule;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\ScheduleSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleSetting>
 */
class ScheduleSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ScheduleSetting::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::query()->first()->id,
            'status' => SituationStatusEnum::ACTIVE->value,
            'lieu_id' => Lieu::query()->inRandomOrder()->first()->id,
            'number_weeks' => 6,
            'days' => json_encode([
                Carbon::MONDAY => [
                    '08:00' => [
                        'start_at' => '08:00',
                        'end_at' => '09:00',
                    ],
                    '12:00' => [
                        'start_at' => '12:00',
                        'end_at' => '13:00',
                    ],
                ],
                Carbon::WEDNESDAY => [
                    '08:00' => [
                        'start_at' => '08:00',
                        'end_at' => '09:00',
                    ],
                    '12:00' => [
                        'start_at' => '12:00',
                        'end_at' => '13:00',
                    ],
                ],
                Carbon::THURSDAY => [
                    '08:00' => [
                        'start_at' => '08:00',
                        'end_at' => '09:00',
                    ],
                    '10:00' => [
                        'start_at' => '10:00',
                        'end_at' => '11:00',
                    ],
                    '12:00' => [
                        'start_at' => '12:00',
                        'end_at' => '13:00',
                    ],
                    '16:00' => [
                        'start_at' => '16:00',
                        'end_at' => '17:00',
                    ],
                ],
                Carbon::FRIDAY => [
                    '08:00' => [
                        'start_at' => '08:00',
                        'end_at' => '09:00',
                    ],
                    '10:00' => [
                        'start_at' => '10:00',
                        'end_at' => '11:00',
                    ],
                    '12:00' => [
                        'start_at' => '12:00',
                        'end_at' => '13:00',
                    ],
                    '16:00' => [
                        'start_at' => '16:00',
                        'end_at' => '17:00',
                    ],
                ],


            ])
        ];
    }
}
