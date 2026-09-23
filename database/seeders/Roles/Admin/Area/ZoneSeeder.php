<?php

namespace database\seeders\Roles\Admin\Area;

use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Zone::factory()->count(6)->create();
        $zones = [
            ['id' => Str::uuid(), 'name' => 'Paris intra-muros'],
            ['id' => Str::uuid(), 'name' => 'Ile de France'],
        ];
        Zone::insert($zones);
    }
}
