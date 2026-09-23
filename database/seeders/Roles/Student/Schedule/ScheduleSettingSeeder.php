<?php

namespace database\seeders\Roles\Student\Schedule;

use App\Models\Roles\Student\Schedule\ScheduleSetting;
use Illuminate\Database\Seeder;

class ScheduleSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ScheduleSetting::factory()->create();
    }
}
