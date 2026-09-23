<?php

namespace database\seeders\Roles\Monitor\User\Informations;

use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Monitor::query()->each(function ($monitor) {
            Billing::factory()->create([
                'monitor_id' => $monitor->id
            ]);
        });
    }
}
