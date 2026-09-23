<?php

namespace database\seeders\Roles\Admin\Area;

use App\Models\Roles\Admin\Area\Zip;
use Illuminate\Database\Seeder;

class ZipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Zip::factory()->count(10)->create();
    }
}
