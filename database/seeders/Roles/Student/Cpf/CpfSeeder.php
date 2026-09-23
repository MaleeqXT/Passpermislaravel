<?php

namespace database\seeders\Roles\Student\Cpf;

use App\Models\Roles\Student\Cpf\Cpf;
use Illuminate\Database\Seeder;

class CpfSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cpf::factory()->count(10)->create();
    }
}
