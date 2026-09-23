<?php

namespace database\seeders\Roles\Student\User\Information;

use App\Models\Roles\Student\User\Information\Call;
use Illuminate\Database\Seeder;

class CallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Call::factory()->count(10)->create();
    }
}
