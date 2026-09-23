<?php

namespace App\Repository\V2\Student\Schedule\Training\Offre;

use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchOffreRepo
{
    /**
     * @param string $offerId
     * @param array|null $attributes
     * @return Builder|Model
     */
    public static function run(string $offerId, array $attributes = null): Model|Builder
    {
        return Offer::query()
            ->where('id', $offerId)
            ->where('status', true)
            ->firstOrFail();
    }
}
