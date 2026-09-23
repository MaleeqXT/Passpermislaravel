<?php

namespace database\seeders\Roles\Student\User\Competency;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Seeder;

class CompetencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subCompetencies = [
            'Maîtriser le véhicule' => [
                'Connaître le tableau de bord',
                'Utiliser correctement les pédales',
                'Manipuler le volant efficacement',
            ],
            'Appréhender la route' => [
                'Observer l’environnement',
                'Anticiper les dangers',
                'Respecter la signalisation',
            ],
            'Circuler dans des conditions variées' => [
                'Conduire en ville',
                'Conduire sur route et autoroute',
                'Adapter la conduite aux conditions météorologiques',
            ],
            'Partager la route avec les autres usagers' => [
                'Respecter les distances de sécurité',
                'Utiliser les clignotants correctement',
                'Comprendre les intentions des autres usagers',
            ],
            'Gérer son autonomie et sa responsabilité' => [
                'Évaluer son niveau de fatigue',
                'Prendre en compte les risques',
                'Adopter une conduite économique et écologique',
            ],
        ];

        foreach ($subCompetencies as $mainCompetencyName => $subCompetencyList) {
            $mainCompetency = MainCompetency::where('name', $mainCompetencyName)->first();

            if ($mainCompetency) {
                foreach ($subCompetencyList as $subCompetency) {
                    Competency::create([
                        'label' => $subCompetency,
                        'status' => SituationStatusEnum::ACTIVE->value,
                        'main_competency_id' => $mainCompetency->id,
                    ]);
                }
            }
        }
    }
}
