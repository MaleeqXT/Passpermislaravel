<?php

namespace Database\Seeders\Theory;

use App\Services\Theory\TheoryQuestionImporter;
use Illuminate\Database\Seeder;

class TheoryQuestionSeeder extends Seeder
{
    public function run(): void
    {
        app(TheoryQuestionImporter::class)->import(
            'original-initial-80',
            OriginalTheoryQuestionBatch::categories(),
            OriginalTheoryQuestionBatch::questions(),
            80
        );
    }
}
