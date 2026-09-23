<?php

namespace database\seeders\Roles\Student\User\Information;

use App\Models\Roles\Student\User\Information\StudentNote;
use Illuminate\Database\Seeder;

class StudentNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentNote::factory()
            ->count(10)
            ->create();
    }
}
