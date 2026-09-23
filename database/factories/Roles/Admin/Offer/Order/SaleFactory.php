<?php

namespace database\factories\Roles\Admin\Offer\Order;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Student\User\Student;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Sale::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::query()->inRandomOrder()->first()?->id,
            'cart_id' => Cart::query()->inRandomOrder()->first()?->id,
            'payment_id' =>  fake()->uuid,
            'payment_status' => $this->faker->randomElement([SaleStatusEnum::PAID->value, SaleStatusEnum::PENDING->value, SaleStatusEnum::REFUNDED->value, SaleStatusEnum::CANCELED->value]),
            'payment_method' => $this->faker->randomElement([SalePaymentMethodEnum::CASH->value, SalePaymentMethodEnum::STRIP->value, SalePaymentMethodEnum::CHECK->value, SalePaymentMethodEnum::TRANSFER->value, SalePaymentMethodEnum::PAYPAL->value]),
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'balance' => $this->faker->randomElement([0, 10]),
            'reference' =>  'AD' . now()->format('y') . '-' . Str::upper(Str::random(6)),
        ];
    }
}
