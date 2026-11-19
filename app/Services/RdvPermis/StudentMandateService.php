<?php

namespace App\Services\RdvPermis;

use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use Throwable;

class StudentMandateService
{
    private const GROUPE_PERMIS = 'B';

    public function __construct(
        private readonly AutoEcoleService $autoEcole,
        private readonly RdvPermisSyncService $sync,
    ) {}

    /**
     * Mapping layer between local columns and the Swagger body CreerMandatApi
     * (POST /api/v2/auto-ecole/mandats, see docs/rdvpermis-api-contract.md):
     *
     *   users.last_name  -> nom            (surname only, never first or full name)
     *   students.neph    -> numeroDossier  (string, format kept as stored)
     *   users.email      -> email          (required locally; provider requires it
     *                                       when the candidate has no RdvPermis account)
     *   permit group     -> groupePermis   (A|B; there is no local permit column,
     *                                       PassPermis handles category B)
     *
     * Only whitespace is normalised. No other local field is ever sent
     * (students.id, first_name, name, boite_type, …).
     *
     * @return array{nom:string, numeroDossier:string, email:string, groupePermis:string}
     */
    public function payloadFor(Student $student): array
    {
        $student->loadMissing('user');
        $user = $student->user;

        $nom = $this->normalizeWhitespace($user?->last_name);
        $numeroDossier = $this->normalizeWhitespace($student->neph);
        $email = trim((string) $user?->email);

        if ($nom === '') {
            throw new InvalidArgumentException('Le nom du candidat est requis avant la synchronisation RdvPermis.');
        }

        if ($numeroDossier === '') {
            throw new InvalidArgumentException('Le numéro de dossier NEPH est requis avant la synchronisation RdvPermis.');
        }

        if ($email === '') {
            throw new InvalidArgumentException('Une adresse e-mail est requise avant la synchronisation RdvPermis.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Une adresse e-mail valide est requise avant la synchronisation RdvPermis.');
        }

        return [
            'nom' => $nom,
            // Do not convert to an integer: the government contract requires a string.
            'numeroDossier' => $numeroDossier,
            'email' => $email,
            // Transmission type (students.boite_type) is intentionally unrelated here.
            'groupePermis' => self::GROUPE_PERMIS,
        ];
    }

    /** Trims and collapses runs of whitespace; never changes other characters. */
    private function normalizeWhitespace(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) ($value ?? '')));
    }

    public function synchronize(User $actor, Student $student): Response
    {
        $payload = $this->payloadFor($student);
        $record = $this->sync->markAttempt(
            $this->sync->recordFor('student_rdvpermis_mandate', (string) $student->getKey())
        );

        try {
            $response = $this->autoEcole->createMandate($actor, $payload);
            $mandateId = $response->json('id');

            $this->sync->markSynced($record, filled($mandateId) ? (string) $mandateId : null, [
                'remote_candidate_id' => $response->json('candidatId'),
                'permit_group' => $response->json('groupePermis') ?? $payload['groupePermis'],
                'remote_school_id' => $response->json('autoEcole.id'),
                'provider_context' => [
                    'environment' => config('rdvpermis.environment'),
                    'api_url' => config('rdvpermis.api_url'),
                    'actor_user_id' => (string) $actor->getKey(),
                ],
            ]);

            return $response;
        } catch (Throwable $exception) {
            $this->sync->markFailed($record, $exception);

            throw $exception;
        }
    }
}
