<?php

namespace database\seeders\Roles\Student\Schedule;

use App\Models\Roles\Student\Schedule\StudentAvailability;
use Illuminate\Database\Seeder;

class StudentAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentAvailability::factory()->count(10)->create();
    }
}
