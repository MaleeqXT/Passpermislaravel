<?php

namespace database\factories\Roles\Admin\Offer\Cart;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Cart::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::query()->inRandomOrder()->first()?->id,
            'status' => $this->faker->randomElement([CartStatusEnum::PENDING->value, CartStatusEnum::PAID->value, CartStatusEnum::CANCELLED->value]),
        ];
    }
}
