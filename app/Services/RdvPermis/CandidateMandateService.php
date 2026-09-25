<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use Illuminate\Http\Client\Response;

class CandidateMandateService
{
    private const SEARCH_PATH = '/api/v2/auto-ecole/candidats-mandats/recherche';

    public function __construct(private readonly ApiClient $client) {}

    /**
     * @param  array{filtre?:array{groupePermis:string,nom?:array{query?:string,match?:string},numeroDossier?:array{query?:string,match?:string}},page?:int,parPage?:int}  $criteria
     */
    public function search(User $user, array $criteria): Response
    {
        return $this->client->post($user, self::SEARCH_PATH, $criteria);
    }
}
