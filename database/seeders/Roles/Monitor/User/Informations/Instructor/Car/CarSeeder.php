<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor\Car;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Car::factory()->count(5)
            ->create();
    }
}
