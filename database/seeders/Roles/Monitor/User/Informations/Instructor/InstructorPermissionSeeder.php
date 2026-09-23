<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorPermission;
use Illuminate\Database\Seeder;

class InstructorPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InstructorPermission::factory()
            ->count(10)
            ->hasMedia(5)
            ->create();
    }
}
