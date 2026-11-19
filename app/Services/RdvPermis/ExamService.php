<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use Illuminate\Http\Client\Response;

class ExamService
{
    private const EXAMS_PATH = '/api/v2/auto-ecole/examens';

    public function __construct(private readonly ApiClient $client) {}

    public function all(User $user, string $groupePermis, string $date): Response
    {
        return $this->client->get($user, self::EXAMS_PATH, [
            'statut' => 'PROGRAMME',
            'groupe-permis' => $groupePermis,
            'date' => $date,
        ]);
    }

    public function find(User $user, string $examenId): Response
    {
        return $this->client->get($user, $this->examPath($examenId));
    }

    public function cancel(User $user, string $examenId): Response
    {
        return $this->client->delete($user, $this->examPath($examenId));
    }

    public function permutables(User $user, string $examenPermuteId, string $date): Response
    {
        return $this->client->get($user, self::EXAMS_PATH.'/permutables', [
            'examen-permute-id' => $examenPermuteId,
            'date' => $date,
        ]);
    }

    public function replacementAllowance(User $user, string $groupePermis, string $date): Response
    {
        return $this->client->get($user, self::EXAMS_PATH.'/nombre-de-remplacement', [
            'groupe-permis' => $groupePermis,
            'date' => $date,
        ]);
    }

    public function dates(User $user, string $groupePermis, string $dateDebut, ?bool $creneauSansCandidat = null): Response
    {
        return $this->client->get($user, self::EXAMS_PATH.'/dates', array_filter([
            'statut' => 'PROGRAMME',
            'groupe-permis' => $groupePermis,
            'date-debut' => $dateDebut,
            'creneauSansCandidat' => $creneauSansCandidat,
        ], static fn (mixed $value): bool => $value !== null));
    }

    public function paginated(User $user, array $query): Response
    {
        return $this->client->get($user, '/api/v2/auto-ecole/examens-pagines', $query);
    }

    public function replace(User $user, string $examenId, string $candidatRemplacantId): Response
    {
        return $this->client->post($user, self::EXAMS_PATH.'/remplacement', [
            'examenId' => $examenId,
            'candidatRemplacantId' => $candidatRemplacantId,
        ]);
    }

    public function permute(User $user, string $examenId1, string $examenId2): Response
    {
        return $this->client->post($user, self::EXAMS_PATH.'/permutation', compact('examenId1', 'examenId2'));
    }

    /**
     * Registers an eligible candidate on an already-reserved group A exam.
     * Swagger (RECETTE, 2026-11-19) still exposes this operation on a v1 path,
     * with both ids in the path and no request body; 200 has no documented body.
     */
    public function registerCandidate(User $user, string $examenId, string $candidatId): Response
    {
        return $this->client->request(
            $user,
            'PUT',
            '/api/v1/auto-ecole/examen-programme/'.rawurlencode($examenId).'/candidat/'.rawurlencode($candidatId).'/validation',
        );
    }

    private function examPath(string $examenId): string
    {
        return self::EXAMS_PATH.'/'.$examenId;
    }
}
