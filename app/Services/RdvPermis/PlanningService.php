<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use Illuminate\Http\Client\Response;

/**
 * Current planning-search API.
 */
class PlanningService
{
    private const SEARCH_PATH = '/api/v2/auto-ecole/planning/recherche';

    public function __construct(private readonly ApiClient $client) {}

    public function search(User $user, string $groupePermis, string $date, string $centreId): Response
    {
        return $this->client->post($user, self::SEARCH_PATH, [
            'filtre' => [
                'groupePermis' => $groupePermis,
                'date' => $date,
                'centreId' => $centreId,
            ],
        ]);
    }
}
