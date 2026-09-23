<?php

namespace database\seeders\Roles\Admin\Promo;

use App\Enums\V2\Admin\Promo\PromoTypeEnum;
use App\Models\Roles\Admin\Promo\Promo;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promos = [
            [
                'location' => 'Acceuil',
                'type' => PromoTypeEnum::PROMO->value,
                'is_active' => false,
            ],
            [
                'location' => 'Acceuil',
                'type' => PromoTypeEnum::CUSTOMIZE_HOME->value,
                'is_active' => true,
            ],
        ];
        foreach ($promos as $promoData) {
            Promo::query()->updateOrCreate([
                'location' => $promoData['location'],
                'type' => $promoData['type'],
            ], $promoData);
        }
    }
}
