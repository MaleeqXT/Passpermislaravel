<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorCertification;
use Illuminate\Database\Seeder;

class InstructorCertificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InstructorCertification::factory()->count(10)
            ->hasMedia(5)
            ->create();
    }
}
