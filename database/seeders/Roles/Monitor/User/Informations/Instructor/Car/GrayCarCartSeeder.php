<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor\Car;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\GrayCarCart;
use Illuminate\Database\Seeder;

class GrayCarCartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GrayCarCart::factory()
            ->count(10)
            ->hasMedia(5)
            ->create();
    }
}
