<?php

namespace database\seeders\Roles\Admin\Area;

use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Seeder;

class LieuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            'Paris intra-muros' => [
                'Boulevard Saint-Germain',
                'Rue de Rennes',
            ],
            'Ile de France' => [
                'Chartres',
            ],
        ];

        foreach ($zones as $zoneName => $places) {
            $zone = Zone::where('name', $zoneName)->first();

            if ($zone) {
                foreach ($places as $place) {
                    Lieu::create([
                        'zone_id' => $zone->id,
                        'name' => $place,
                    ]);
                }
            }
        }
    }
}
