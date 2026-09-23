<?php

namespace database\seeders\Roles\Student\User;

use App\Models\Roles\Student\User\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Wallet::factory()->count(3)->create();
    }
}
