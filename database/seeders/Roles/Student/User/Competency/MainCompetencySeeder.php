<?php

namespace database\seeders\Roles\Student\User\Competency;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Seeder;

class MainCompetencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // MainCompetency::factory()->count(5)->create();
        $competencies = [
            ['name' => 'Maîtriser le véhicule', 'label' => 'Contrôle et manipulation', 'position' => 1],
            ['name' => 'Appréhender la route', 'label' => 'Observation et anticipation', 'position' => 2],
            ['name' => 'Circuler dans des conditions variées', 'label' => 'Adaptation à l’environnement', 'position' => 3],
            ['name' => 'Partager la route avec les autres usagers', 'label' => 'Interactivité et communication', 'position' => 4],
            ['name' => 'Gérer son autonomie et sa responsabilité', 'label' => 'Conduite responsable', 'position' => 5],
        ];

        foreach ($competencies as $competency) {
            MainCompetency::create($competency);
        }
    }
}
