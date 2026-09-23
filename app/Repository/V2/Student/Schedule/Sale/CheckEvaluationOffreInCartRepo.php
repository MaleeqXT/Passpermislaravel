<?php

namespace App\Repository\V2\Student\Schedule\Sale;

use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\User\Student;

class CheckEvaluationOffreInCartRepo
{
    public static function run(Student $student, array $attributes = [])
    {
        $offres = collect($attributes)->pluck('offer_id')->toArray();

        //  We only check if offers exist, but we don’t block evaluation offers anymore
        return Offer::query()
            ->whereIn('id', $offres)
            ->get(); // just return them normally without restriction
    }
}
