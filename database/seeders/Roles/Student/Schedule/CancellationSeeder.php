<?php

namespace database\seeders\Roles\Student\Schedule;

use App\Models\Roles\Student\Schedule\Cancellation;
use Illuminate\Database\Seeder;

class CancellationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cancellation::factory()
            ->count(10)
            ->hasMedia(5)
            ->create();
    }
}
