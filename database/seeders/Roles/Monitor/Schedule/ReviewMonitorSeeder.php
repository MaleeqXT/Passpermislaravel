<?php

namespace database\seeders\Roles\Monitor\Schedule;

use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Seeder;

class ReviewMonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Monitor::query()->each(function ($monitor) {
            ReviewMonitor::factory()->create([
                'monitor_id' => $monitor->id
            ]);
        });
    }
}
