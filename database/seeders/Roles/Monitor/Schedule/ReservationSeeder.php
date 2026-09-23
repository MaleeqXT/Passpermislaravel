<?php

namespace database\seeders\Roles\Monitor\Schedule;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Reservation::factory()->count(50)->create();
    }
}
