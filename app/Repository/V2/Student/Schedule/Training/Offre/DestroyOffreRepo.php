<?php

namespace App\Repository\V2\Student\Schedule\Training\Offre;


use App\Models\Roles\Admin\Offer\Offer;
use Exception;


class DestroyOffreRepo
{
    /**
     * @param Offer $offer
     * @return bool|null
     * @throws Exception
     */
    public static function run(Offer $offer): ?bool
    {
        try {
            // This action is called only by “Supprimer définitivement”.
            // Archiving is handled separately by the status endpoint.
            return $offer->forceDelete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
