<?php

namespace database\seeders\Roles\Student\Schedule;

use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\Rating;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Monitor::query()->each(function ($monitor) {
            Rating::factory()->create([
                'monitor_id' => $monitor->id
            ]);
        });
    }
}
