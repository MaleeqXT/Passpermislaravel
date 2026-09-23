<?php

namespace Database\Seeders\Media;

use App\Models\Media\StorageMedia;
use Illuminate\Database\Seeder;

class StorageMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StorageMedia::factory()
            ->count(30)
            //   ->hasMedia(5)
            ->create();
    }
}
