<?php

namespace database\factories\Roles\Admin\Offer;

use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Admin\Offer\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Offer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $caracteristiques = "<p> ✓ " . $this->faker->text(30) . "</p><p> ✓ " . $this->faker->text(30) . "</p><p> ✓ " . $this->faker->text(30) . '</p>';
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->text(30),
            'caracteristiques' => $caracteristiques,
            'price_ht' => $this->faker->numberBetween(0, 100),
            'original_price' => $this->faker->numberBetween(0, 100),
            'discounted_price' => $this->faker->numberBetween(0, 100),
            'balance' => $this->faker->numberBetween(0, 30),
            'is_auto' => $this->faker->boolean(),
            'multi_payment' => $this->faker->numberBetween(0, 10),
            'type' => $this->faker->randomElement([OffreTypeStatusEnum::CODE->value, OffreTypeStatusEnum::CONDUITE->value]),
            'type_offre' => $this->faker->randomElement([
                OffreTypeEnum::EXAMEN->value,
                OffreTypeEnum::FORFAIT->value,
                OffreTypeEnum::CODE_ONLINE->value,
            ]),
            'status' => $this->faker->randomElement([0, 1]),
            'color' => $this->faker->randomElement(['black', 'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'brown', 'grey']),
        ];
    }
}
