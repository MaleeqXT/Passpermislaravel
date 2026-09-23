<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use Illuminate\Http\Client\Response;

/**
 * Confirmed RECETTE1 Auto-Ecole operations.
 *
 * Paths are intentionally limited to the current Swagger operations that have
 * been verified for this project. Additional operations belong here only once
 * their v2 request schema has been confirmed from Swagger.
 */
class AutoEcoleService
{
    private const CURRENT_SCHOOL_PATH = '/api/v2/auto-ecole/moi';

    private const EMPLOYEES_PATH = '/api/v2/auto-ecole/employes';

    private const MANDATES_PATH = '/api/v2/auto-ecole/mandats';

    public function __construct(private readonly ApiClient $client) {}

    public function currentSchool(User $user): Response
    {
        return $this->client->get($user, self::CURRENT_SCHOOL_PATH);
    }

    public function employees(User $user): Response
    {
        return $this->client->get($user, self::EMPLOYEES_PATH);
    }

    /**
     * Creates a mandate for an already-existing RDVPermis candidate.
     * Candidate lookup/creation remains a separate government workflow.
     *
     * @param array{nom:string, numeroDossier:string, email:string, groupePermis:string} $payload
     */
    public function createMandate(User $user, array $payload): Response
    {
        return $this->client->post($user, self::MANDATES_PATH, $payload);
    }
}
