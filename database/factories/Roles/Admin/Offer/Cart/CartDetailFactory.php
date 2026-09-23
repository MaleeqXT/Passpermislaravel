<?php

namespace database\factories\Roles\Admin\Offer\Cart;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartDetail>
 */
class CartDetailFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CartDetail::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::query()->inRandomOrder()->first()?->id,
            'offer_id' => Offer::query()->inRandomOrder()->first()?->id,
            'quantity' => $this->faker->numberBetween(1, 10),
            'tranches' => $this->faker->numberBetween(0, 10),
        ];
    }
}
