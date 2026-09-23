<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use Illuminate\Database\Seeder;

class InstructorDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InstructorDocument::factory()
            ->count(10)
            ->create();
    }
}
