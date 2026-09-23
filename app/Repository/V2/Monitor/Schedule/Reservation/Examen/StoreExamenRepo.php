<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Examen;

use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\Exam\StudentExam;

class StoreExamenRepo
{
    /**
     * @param $student
     * @param $offer_id
     * @return void
     */
    public static function run($student, $offer_id)
    {
        $offer = self::getOffer($offer_id);

        if ($offer) {
            self::createStudentExam($student, $offer);
        }
    }

    /**
     * Get the offer based on the offer ID.
     */
    private static function getOffer($offer_id)
    {
        return Offer::query()
            ->where('id', $offer_id)
            ->whereIn('type_offre', [
                OffreTypeEnum::EXAMEN->value
            ])
            ->first();
    }


    /**
     * Create a student exam entry.
     */
    private static function createStudentExam($student, $offer): void
    {
        StudentExam::query()->create([
            'student_id' => $student->id,
            'status' => ExamenStatusEnum::PENDING->value,
            'is_auto' => $offer->is_auto,
        ]);
    }
}
