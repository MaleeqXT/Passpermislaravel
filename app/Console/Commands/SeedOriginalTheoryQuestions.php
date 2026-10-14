<?php

namespace App\Console\Commands;

use App\Services\Theory\TheoryQuestionImporter;
use Database\Seeders\Theory\OriginalTheoryQuestionBatch;
use Illuminate\Console\Command;

class SeedOriginalTheoryQuestions extends Command
{
    protected $signature = 'theory:seed-original {--limit=80 : Maximum de questions à importer dans cette exécution}';

    protected $description = 'Importe ou reprend le lot éditorial original Code de la Route';

    public function handle(TheoryQuestionImporter $importer): int
    {
        $result = $importer->import(
            'original-initial-80',
            OriginalTheoryQuestionBatch::categories(),
            OriginalTheoryQuestionBatch::questions(),
            (int) $this->option('limit')
        );

        $this->table(array_keys($result), [array_values($result)]);
        $this->line('Pour reprendre le lot, relancez exactement la même commande. Les entrées déjà traitées ne sont pas recréées.');

        return self::SUCCESS;
    }
}
