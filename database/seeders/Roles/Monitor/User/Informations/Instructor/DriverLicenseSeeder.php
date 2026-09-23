<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Informations\Instructor\DriverLicense;
use Illuminate\Database\Seeder;

class DriverLicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DriverLicense::factory()->count(10)
            ->hasMedia(5)
            ->create();
    }
}
