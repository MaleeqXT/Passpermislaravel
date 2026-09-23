<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor\Car;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\CarAssurance;
use Illuminate\Database\Seeder;

class CarAssuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CarAssurance::factory()
            ->count(10)
            ->hasMedia(5)
            ->create();
    }
}
