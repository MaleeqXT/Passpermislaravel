<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $reference = [
            [
                'name' => 'Maîtriser le maniement du véhicule dans un trafic faible ou nul',
                'label' => 'C1',
                'questions' => [
                    'Connaître les principaux organes et commandes du véhicule',
                    "Entrer, s'installer au poste de conduite et en sortir sans surprendre",
                    'Tenir, tourner le volant et maintenir la trajectoire',
                    'Démarrer et arrêter le véhicule sans caler',
                    'Démarrer en côte et dans un faux-plat, sans reculer',
                    'Déplacer le véhicule à allure lente',
                    "Doser l'accélération et le freinage à diverses allures",
                    'Monter les vitesses sans erreurs et les passer au bon moment',
                    'Freiner et rétrograder sans lâcher le frein',
                    "Rétrograder sans freiner quand c'est nécessaire",
                    "Effectuer une première roulante à l'allure du pas",
                    "Diriger la voiture en ligne droite et en courbe sans faire d'écarts de trajectoire",
                    "Effectuer les contrôles vers l'arrière, sur les côtés et avertir au bon moment",
                    'Effectuer une marche arrière et un demi-tour en sécurité',
                ],
            ],
            [
                'name' => 'Appréhender la route et circuler dans des conditions normales',
                'label' => 'C2',
                'questions' => [
                    'Rechercher la signalisation et les indices utiles et en tenir compte',
                    'Positionner le véhicule sur la voie de circulation adaptée',
                    "Adapter l'allure aux situations",
                    'Tourner à droite et à gauche en agglomération',
                    'Détecter, identifier et franchir les intersections',
                    'Céder le passage et marquer un arrêt précis à un stop',
                    'Aborder et franchir les feux',
                    'Franchir les carrefours à sens giratoire et les ronds-points',
                    'Stationner en épi ou en bataille, à droite ou à gauche',
                    'Stationner en créneau à droite ou à gauche',
                ],
            ],
            [
                'name' => 'Circuler dans des conditions difficiles et partager la route avec les autres usagers',
                'label' => 'C3',
                'questions' => [
                    'Évaluer et maintenir les distances de sécurité',
                    'Croiser et anticiper les croisements difficiles',
                    'Dépasser et être dépassé(e)',
                    'Passer les virages et conduire en côte et en descente',
                    "Se comporter à l'égard des diverses catégories d'usagers avec respect et courtoisie",
                    "S'insérer sur une voie rapide, y circuler et en sortir",
                    'Conduire dans une circulation dense',
                    'Connaître les règles de la circulation interfiles des motos et en tenir compte',
                    "Conduire quand l'adhérence et la visibilité sont réduites",
                    'Conduire dans les tunnels, sur les ponts, ...',
                ],
            ],
            [
                'name' => 'Pratiquer une conduite autonome, sûre et économique',
                'label' => 'C4',
                'questions' => [
                    'Suivre un itinéraire de manière autonome',
                    'Préparer et effectuer un voyage longue distance',
                    'Connaître les principaux facteurs de risque et les recommandations à appliquer',
                    "Connaître les comportements à adopter lors d'un accident de la route",
                    "Avoir fait l'expérience des aides à la conduite du véhicule",
                    "Acquérir des notions sur l'entretien, le dépannage et les situations d'urgence",
                    "Pratiquer l'écoconduite",
                ],
            ],
        ];

        foreach ($reference as $index => $group) {
            $position = $index + 1;
            $main = MainCompetency::query()->firstOrNew(['position' => $position]);
            $main->fill([
                'name' => $group['name'],
                'label' => $group['label'],
                'status' => SituationStatusEnum::ACTIVE->value,
                'position' => $position,
            ]);
            $main->save();

            foreach ($group['questions'] as $questionIndex => $label) {
                Competency::query()->updateOrCreate(
                    [
                        'main_competency_id' => $main->id,
                        'position' => $questionIndex + 1,
                    ],
                    [
                        'label' => $label,
                        'status' => SituationStatusEnum::ACTIVE->value,
                    ],
                );
            }

            // Preserve historical ratings but keep obsolete questions out of the
            // active C1–C4 reference shown to monitors.
            $main->competencies()
                ->where('position', '>', count($group['questions']))
                ->update(['status' => SituationStatusEnum::INACTIVE->value]);
        }
    }

    public function down(): void
    {
        // Reference data is deliberately retained on rollback so historical
        // monitor ratings remain linked to their original questions.
    }
};
