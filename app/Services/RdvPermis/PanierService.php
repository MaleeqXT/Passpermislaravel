<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use Illuminate\Http\Client\Response;

/**
 * Current "Nouveau Panier" API. RDVPermis owns basket lifetime and expiry.
 */
class PanierService
{
    private const PANIERS_PATH = '/api/v2/auto-ecole/paniers';

    public function __construct(private readonly ApiClient $client) {}

    public function all(User $user): Response
    {
        return $this->client->get($user, self::PANIERS_PATH);
    }

    public function create(User $user, ?string $employeAutoEcoleId = null): Response
    {
        if ($employeAutoEcoleId === null) {
            // The employee field is optional in the current contract: do not send
            // an invented null JSON value when the caller did not provide one.
            return $this->client->request($user, 'POST', self::PANIERS_PATH);
        }

        return $this->client->post($user, self::PANIERS_PATH, [
            'employeAutoEcoleId' => $employeAutoEcoleId,
        ]);
    }

    public function find(User $user, string $panierId): Response
    {
        return $this->client->get($user, $this->panierPath($panierId));
    }

    public function delete(User $user, string $panierId): Response
    {
        return $this->client->delete($user, $this->panierPath($panierId));
    }

    /**
     * HTTP 207 is intentionally returned unchanged by ApiClient because it is a
     * documented per-slot reservation result, not a transport failure.
     */
    public function validate(User $user, string $panierId): Response
    {
        return $this->client->request($user, 'POST', $this->panierPath($panierId).'/valider');
    }

    public function addSlot(User $user, string $panierId, string $creneauId, ?bool $inclureEstCandidatObligatoire = null): Response
    {
        return $this->client->request($user, 'POST', $this->panierPath($panierId).'/creneaux', array_filter([
            'query' => $inclureEstCandidatObligatoire === null ? null : [
                'inclureEstCandidatObligatoire' => $inclureEstCandidatObligatoire,
            ],
            'json' => ['creneauId' => $creneauId],
        ]));
    }

    /** HTTP 207 is a documented per-slot business result and is returned unchanged. */
    public function addSlots(User $user, string $panierId, array $creneauxId, ?bool $inclureEstCandidatObligatoire = null): Response
    {
        return $this->client->request($user, 'POST', $this->panierPath($panierId).'/creneaux-multiples', array_filter([
            'query' => $inclureEstCandidatObligatoire === null ? null : [
                'inclureEstCandidatObligatoire' => $inclureEstCandidatObligatoire,
            ],
            'json' => ['creneauxId' => $creneauxId],
        ]));
    }

    public function removeSlot(User $user, string $panierId, string $creneauId): Response
    {
        return $this->client->delete($user, $this->panierPath($panierId).'/creneaux/'.$creneauId);
    }

    /** Passing null removes the candidate assigned to the basket slot. */
    public function assignCandidate(User $user, string $panierId, string $creneauId, ?string $candidatId): Response
    {
        return $this->client->put($user, $this->panierPath($panierId).'/creneaux/'.$creneauId.'/candidat', [
            'candidatId' => $candidatId,
        ]);
    }

    private function panierPath(string $panierId): string
    {
        return self::PANIERS_PATH.'/'.$panierId;
    }
}
