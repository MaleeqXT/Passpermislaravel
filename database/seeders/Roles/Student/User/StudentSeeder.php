<?php

namespace database\seeders\Roles\Student\User;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Student::factory()->count(10)->create();
    }
}
