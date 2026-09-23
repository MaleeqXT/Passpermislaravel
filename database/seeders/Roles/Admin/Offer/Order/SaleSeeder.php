<?php

namespace database\seeders\Roles\Admin\Offer\Order;

use App\Models\Roles\Admin\Offer\Order\Sale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sale::factory()->count(6)->create();
    }
}
