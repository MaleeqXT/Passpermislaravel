<?php

namespace database\seeders\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Informations\Instructor\IdentityRecord;
use Illuminate\Database\Seeder;

class IdentityRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        IdentityRecord::factory()->count(10)
            ->hasMedia(5)
            ->create();
    }
}
