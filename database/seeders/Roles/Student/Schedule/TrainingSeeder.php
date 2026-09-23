<?php

namespace database\seeders\Roles\Student\Schedule;

use App\Models\Roles\Student\Schedule\Training;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Training::factory()->count(25)->create();
    }
}
