<?php

namespace database\factories\Roles\Student\User;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Wallet::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'balance' => $this->faker->randomFloat(2, 0, 100),
            'status' => 1,
            'offer_id' => Offer::query()->inRandomOrder()->first()->id,
            'student_id' => Student::query()->inRandomOrder()->first()->id,
        ];
    }
}
