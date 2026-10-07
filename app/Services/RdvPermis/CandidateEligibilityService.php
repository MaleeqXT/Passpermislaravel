<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use App\Exceptions\RdvPermisApiException;
use Illuminate\Http\Client\Response;

class CandidateEligibilityService
{
    public function __construct(private readonly ApiClient $client) {}

    public function search(User $user, array $criteria): Response
    {
        return $this->client->post($user, '/api/v2/auto-ecole/candidats/recherche', $criteria);
    }

    public function requireEligible(User $user, string $creneauId, string $candidateId): void
    {
        $results = $this->search($user, ['filtre' => ['creneauId' => $creneauId]])->json();
        if (! is_array($results) || ! array_is_list($results)) {
            throw new RdvPermisApiException('La réponse d’éligibilité RdvPermis est indisponible. Réessayez plus tard.', 502);
        }
        foreach ($results as $result) {
            if (is_array($result) && data_get($result, 'candidat.id') === $candidateId
                && data_get($result, 'eligibilite.estEligible') === true) {
                return;
            }
        }

        throw new RdvPermisApiException('RdvPermis ne confirme pas l’éligibilité de ce candidat pour ce créneau.', 422);
    }
}
