<?php

namespace database\factories\Roles\Monitor\User\Informations;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Billing>
 */
class BillingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Billing::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::query()->first()->id,
            'num_facture' => 'AD-' . $this->faker->unique()->randomNumber(8, true),
            'from' => $this->faker->date(),
            'to' => $this->faker->date(),
            'date_paiement' => $this->faker->date(),
            'montant' => $total = $this->faker->randomFloat(2, 1, 1000),
            'details' => [
                'num_heures_f' => $numb_heur = $this->faker->randomNumber(2),
                'num_heures_nf' => $numb_heur_nf = $this->faker->randomNumber(2),
                //    'etpB' => $numb_heur / $this->faker->randomFloat(2, 1, 100),
                'prix_heure' => $this->faker->randomFloat(2, 1, 100),
                'total' => $total
            ],
        ];
    }
}
