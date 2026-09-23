<?php

namespace database\seeders\Roles\Student\Schedule;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Illuminate\Database\Seeder;

class TrainingProposalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TrainingProposal::factory()->count(20)->create();
    }
}
