<?php

namespace database\seeders\Roles\Admin\Offer\Cart;

use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use Illuminate\Database\Seeder;

class CartDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CartDetail::factory()->count(20)->create();
    }
}
