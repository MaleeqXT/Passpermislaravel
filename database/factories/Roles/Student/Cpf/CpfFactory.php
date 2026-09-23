<?php

namespace database\factories\Roles\Student\Cpf;

use App\Enums\V2\Student\Cpf\EleveCpfStatusEnum;
use App\Models\Roles\Student\Cpf\Cpf;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Student\Cpf\Cpf>
 */
class CpfFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Cpf::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'start_at' => $this->faker->date(),
            'end_at' => $this->faker->date(),
            'date_verif' => $this->faker->date(),
            'comment' => $this->faker->text(),
            'status' => Arr::random([
                EleveCpfStatusEnum::Accepte->value,
                EleveCpfStatusEnum::EnFormation->value,
                EleveCpfStatusEnum::SortieFormation->value,
                EleveCpfStatusEnum::ServiceFaitDeclare->value,
                EleveCpfStatusEnum::ServiceFaitValide->value,
                EleveCpfStatusEnum::Facture->value,
                EleveCpfStatusEnum::KO->value,
            ]),
            'student_id' => \App\Models\Roles\Student\User\Student::query()->inRandomOrder()->first()->id,
            'user_id' => \App\Models\User::query()->inRandomOrder()->first()->id,
            'offer_id' => \App\Models\Roles\Admin\Offer\Offer::query()->inRandomOrder()->first()->id,


        ];
    }
}
