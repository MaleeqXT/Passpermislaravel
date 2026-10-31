<?php

namespace Database\Seeders;

use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryQuestion;
use App\Models\Theory\TheorySeries;
use App\Services\Theory\TheoryQuestionDeduplicator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TheoryIllustratedDemoQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'public_id' => 'DEMO-ILLUS-001', 'category' => 'circulation-routiere', 'difficulty' => 'medium',
                'question_text' => 'À cette intersection, je dois céder le passage aux véhicules qui circulent sur la route transversale.',
                'explanation' => 'Le marquage au sol et le panneau « Cédez le passage » imposent de laisser passer les usagers circulant sur la voie transversale avant de m’engager.',
                'image_path' => 'theory/questions/demo-illustrated-5/priority-intersection.png',
                'options' => [['Oui', true], ['Non', false], ['Je passe sans ralentir', false], ['Seulement aux cyclistes', false]],
            ],
            [
                'public_id' => 'DEMO-ILLUS-002', 'category' => 'circulation-routiere', 'difficulty' => 'easy',
                'question_text' => 'Le feu est rouge et un piéton traverse. Je dois :',
                'explanation' => 'Un feu rouge impose l’arrêt avant la ligne d’effet ou, à défaut, avant le passage piéton. Le piéton qui traverse doit pouvoir terminer sa traversée en sécurité.',
                'image_path' => 'theory/questions/demo-illustrated-5/red-light-pedestrian.png',
                'options' => [['M’arrêter avant le passage piéton', true], ['Poursuivre lentement', false], ['Klaxonner pour prévenir le piéton', false], ['Contourner le passage piéton', false]],
            ],
            [
                'public_id' => 'DEMO-ILLUS-003', 'category' => 'circulation-routiere', 'difficulty' => 'medium',
                'question_text' => 'La ligne est continue à l’approche du virage. Je peux dépasser le tracteur.',
                'explanation' => 'Le franchissement ou le chevauchement d’une ligne continue est interdit. La visibilité réduite dans le virage renforce le risque : je patiente derrière le tracteur.',
                'image_path' => 'theory/questions/demo-illustrated-5/overtaking-tractor.png',
                'options' => [['Oui, car il roule lentement', false], ['Non', true], ['Oui, après un appel de phare', false], ['Oui, si je roule à faible vitesse', false]],
            ],
            [
                'public_id' => 'DEMO-ILLUS-004', 'category' => 'conducteur', 'difficulty' => 'medium',
                'question_text' => 'Sur autoroute sèche, pour conserver une distance de sécurité, je dois :',
                'explanation' => 'La règle pratique est de conserver au moins deux secondes avec le véhicule qui précède. Cette marge permet de réagir et de freiner sans collision en cas d’imprévu.',
                'image_path' => 'theory/questions/demo-illustrated-5/following-distance.png',
                'options' => [['Garder au moins deux secondes d’écart', true], ['Rester très près pour faciliter le dépassement', false], ['Me fier uniquement à la longueur d’une voiture', false], ['Attendre de freiner au dernier moment', false]],
            ],
            [
                'public_id' => 'DEMO-ILLUS-005', 'category' => 'circulation-routiere', 'difficulty' => 'easy',
                'question_text' => 'Ce panneau circulaire indique :',
                'explanation' => 'Un panneau circulaire bordé de rouge portant le nombre 50 fixe une vitesse maximale autorisée de 50 km/h à partir de son implantation, sauf signalisation ultérieure différente.',
                'image_path' => 'theory/questions/demo-illustrated-5/speed-limit-50.png',
                'options' => [['Une vitesse maximale autorisée de 50 km/h', true], ['Une vitesse minimale obligatoire de 50 km/h', false], ['La fin d’une limitation à 50 km/h', false], ['Une vitesse conseillée de 50 km/h', false]],
            ],
        ];

        DB::transaction(function () use ($questions) {
            $categories = TheoryCategory::query()->pluck('id', 'code');
            $deduplicator = app(TheoryQuestionDeduplicator::class);
            $questionIds = [];

            foreach ($questions as $definition) {
                $question = TheoryQuestion::firstOrCreate(
                    ['public_id' => $definition['public_id']],
                    [
                        'category_id' => $categories[$definition['category']],
                        'question_text' => $definition['question_text'],
                        'difficulty' => $definition['difficulty'],
                        'answer_type' => 'single',
                        'explanation' => $definition['explanation'],
                        'status' => 'published',
                        'is_active' => true,
                        'requires_review' => false,
                        'image_path' => $definition['image_path'],
                        'media_path' => $definition['image_path'],
                        'media_type' => 'image',
                        'content_hash' => $deduplicator->hash($definition['question_text']),
                    ]
                );

                if (! $question->options()->exists()) {
                    $question->options()->createMany(collect($definition['options'])->map(
                        fn (array $option, int $index) => ['label' => $option[0], 'is_correct' => $option[1], 'sort_order' => $index + 1]
                    )->all());
                }
                $questionIds[] = $question->id;
            }

            $series = TheorySeries::firstOrCreate(
                ['title' => 'Série Démo — 5 Questions Illustrées'],
                ['type' => 'training', 'description' => 'Série de démonstration interne contenant cinq questions illustrées originales.', 'status' => 'draft', 'sort_order' => 999]
            );
            foreach ($questionIds as $index => $questionId) {
                $series->seriesQuestions()->firstOrCreate(['question_id' => $questionId], ['sort_order' => $index + 1]);
            }
        });
    }
}
