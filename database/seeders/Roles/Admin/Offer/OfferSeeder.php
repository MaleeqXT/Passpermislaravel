<?php

namespace Database\Seeders\Roles\Admin\Offer;

use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offres = [
            [
                'name' => 'Pack Code Online',
                'description' => 'Accès illimité au Code de la Route en ligne pendant 6 mois.',
                'caracteristiques' => "<p>✓ Plus de 2000 questions</p><p>✓ Examens blancs inclus</p><p>✓ Cours en vidéo</p>",
                'price_ht' => 19,
                'original_price' => 29,
                'final_price' => 29,
                'balance' => 0,
                'is_auto' => false,
                'multi_payment' => 0,
                'type' => OffreTypeStatusEnum::CODE->value,
                'type_offre' => OffreTypeEnum::CODE_ONLINE->value,
                'status' => 1,
                'color' => '#007BFF', // Bleu
            ],
            [
                'name' => '1 Heure d’Évaluation',
                'description' => 'Évaluation initiale obligatoire avant de commencer la formation pratique.',
                'caracteristiques' => "<p>✓ Bilan de votre niveau</p><p>✓ Conseils personnalisés</p><p>✓ Moniteur diplômé</p>",
                'price_ht' => 39,
                'original_price' => 49,
                'discounted_price' => 39,
                'final_price' => 39,
                'balance' => 1,
                'is_auto' => false,
                'multi_payment' => 0,
                'type' => OffreTypeStatusEnum::CONDUITE->value,
                'type_offre' => OffreTypeEnum::EXAMEN->value,
                'status' => 1,
                'color' => '#DC3545', // Rouge
            ],
            [
                'name' => 'Forfait 10 Heures de Conduite',
                'description' => 'Idéal pour ceux qui ont déjà de l’expérience et veulent se perfectionner.',
                'caracteristiques' => "<p>✓ 10 heures de conduite</p><p>✓ Moniteur expérimenté</p><p>✓ Réservation flexible</p>",
                'price_ht' => 349,
                'original_price' => 399,
                'discounted_price' => 349,
                'final_price' => 349,
                'balance' => 10,
                'is_auto' => true,
                'multi_payment' => 3,
                'type' => OffreTypeStatusEnum::CONDUITE->value,
                'type_offre' => OffreTypeEnum::FORFAIT->value,
                'status' => 1,
                'color' => '#28A745', // Vert
            ],
            [
                'name' => 'Forfait 25 Heures de Conduite',
                'description' => 'Formation complète pour préparer l’examen du permis B.',
                'caracteristiques' => "<p>✓ 20 heures de conduite</p><p>✓ Apprentissage progressif</p><p>✓ Pratique en conditions réelles</p>",
                'price_ht' => 699,
                'original_price' => 749,
                'final_price' => 749,
                'balance' => 25,
                'is_auto' => true,
                'multi_payment' => 4,
                'type' => OffreTypeStatusEnum::CONDUITE->value,
                'type_offre' => OffreTypeEnum::FORFAIT->value,
                'status' => 1,
                'color' => '#FFC107', // Jaune
            ],
            [
                'name' => 'Pack Permis Complet (Code + 30h Conduite)',
                'description' => 'Tout ce qu’il faut pour réussir son permis en un seul pack.',
                'caracteristiques' => "<p>✓ Accès au code illimité</p><p>✓ 30h heures de conduite</p><p>✓ Inscription à l’examen incluse</p>",
                'price_ht' => 849,
                'original_price' => 899,
                'final_price' => 899,
                'balance' => 30,
                'is_auto' => true,
                'multi_payment' => 6,
                'type' => OffreTypeStatusEnum::CONDUITE->value,
                'type_offre' => OffreTypeEnum::FORFAIT->value,
                'status' => 1,
                'color' => '#FD7E14', // Orange
            ],
            [
                'name' => 'Cours de Perfectionnement Post-Permis',
                'description' => 'Idéal pour gagner en confiance après l’obtention du permis.',
                'caracteristiques' => "<p>✓ 20 heures de conduite</p><p>✓ Travail sur les points faibles</p><p>✓ Conduite en conditions réelles</p>",
                'price_ht' => 129,
                'original_price' => 199,
                'final_price' => 150,
                'discounted_price' => 150,
                'balance' => 20,
                'is_auto' => true,
                'multi_payment' => 2,
                'type' => OffreTypeStatusEnum::CONDUITE->value,
                'type_offre' => OffreTypeEnum::FORFAIT->value,
                'status' => 1,
                'color' => '#6C757D', // Gris
            ],
        ];

        foreach ($offres as $offre) {
            Offer::create($offre);
        }
    }
}
