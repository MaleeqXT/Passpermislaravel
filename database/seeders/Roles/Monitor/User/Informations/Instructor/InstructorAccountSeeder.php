<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorAccount;
use Illuminate\Database\Seeder;

class InstructorAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InstructorAccount::factory()->count(2)->create();
    }
}
