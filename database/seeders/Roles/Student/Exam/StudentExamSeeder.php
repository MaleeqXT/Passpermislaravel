<?php

namespace database\seeders\Roles\Student\Exam;

use App\Models\Roles\Student\Exam\StudentExam;
use Illuminate\Database\Seeder;

class StudentExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentExam::factory()->count(10)->create();
    }
}
