<?php

namespace Database\Factories\Roles\Monitor\User\Informations\Instructor\Car;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Car::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cars = [
            ['marque' => 'Renault', 'modele' => 'Clio IV', 'color' => 'Bleu'],
            ['marque' => 'Peugeot', 'modele' => '208', 'color' => 'Rouge'],
            ['marque' => 'Volkswagen', 'modele' => 'Golf 7', 'color' => 'Noir'],
            ['marque' => 'Toyota', 'modele' => 'Yaris', 'color' => 'Blanc'],
            ['marque' => 'Ford', 'modele' => 'Fiesta', 'color' => 'Gris'],
            ['marque' => 'Citroën', 'modele' => 'C3', 'color' => 'Vert'],
            ['marque' => 'Opel', 'modele' => 'Corsa', 'color' => 'Jaune'],
        ];

        $car = $this->faker->randomElement($cars);

        return [
            'marque' => $car['marque'],
            'modele' => $car['modele'],
            'color' => $car['color'],
            'immatriculation' => strtoupper($this->faker->bothify('??###??')),
            'date_achat' => $this->faker->date('Y-m-d', '-3 years'),
            'is_auto' => $this->faker->boolean(30), // 30% automatic, 70% manual
            'monitor_id' => Monitor::query()->inRandomOrder()->first()?->id,
            'date_control_tech' => $this->faker->date('Y-m-d', '-1 year'),
            'date_assurance' => $this->faker->date('Y-m-d', 'now'),
        ];
    }
}
