<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use Illuminate\Http\Client\Response;

class CentreService
{
    private const CENTRES_PATH = '/api/v2/auto-ecole/centres';

    public function __construct(private readonly ApiClient $client) {}

    public function search(User $user, string $codeDepartement, string $groupePermis, ?bool $estFerme = null): Response
    {
        return $this->client->post($user, self::CENTRES_PATH.'/recherche', [
            'filtre' => array_filter([
                'codeDepartement' => $codeDepartement,
                'groupePermis' => $groupePermis,
                'estFerme' => $estFerme,
            ], static fn (mixed $value): bool => $value !== null),
        ]);
    }

    public function find(User $user, string $centreId): Response
    {
        return $this->client->get($user, self::CENTRES_PATH.'/'.$centreId);
    }

    public function favorites(User $user): Response
    {
        return $this->client->get($user, '/api/v2/auto-ecole/centres-favoris');
    }

    /** @param array<int, array{centreId?: string, priorite?: int}> $centresFavorisInput */
    public function saveFavorites(User $user, array $payload): Response
    {
        return $this->client->post($user, '/api/v2/auto-ecole/centres-favoris', $payload);
    }
}
