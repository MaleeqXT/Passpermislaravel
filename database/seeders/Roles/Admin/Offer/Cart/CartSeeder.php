<?php

namespace database\seeders\Roles\Admin\Offer\Cart;

use App\Models\Roles\Admin\Offer\Cart\Cart;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cart::factory()->count(3)->create();
    }
}
