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
     * @return array{nom:string, numeroDossier:string, email:string, groupePermis:string}
     */
    public function payloadFor(Student $student): array
    {
        $student->loadMissing('user');
        $user = $student->user;

        if (! $user || ! filled($user->last_name)) {
            throw new InvalidArgumentException('Le nom du candidat est requis avant la synchronisation RdvPermis.');
        }

        if (! filled($student->neph)) {
            throw new InvalidArgumentException('Le numéro de dossier NEPH est requis avant la synchronisation RdvPermis.');
        }

        if (! filled($user->email) || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Une adresse e-mail valide est requise avant la synchronisation RdvPermis.');
        }

        return [
            'nom' => trim((string) $user->last_name),
            // Do not convert to an integer: the government contract requires a string.
            'numeroDossier' => (string) $student->neph,
            'email' => trim((string) $user->email),
            // PassPermis currently handles category-B candidates. Transmission
            // type (students.boite_type) is intentionally unrelated here.
            'groupePermis' => self::GROUPE_PERMIS,
        ];
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

            $this->sync->markSynced($record, filled($mandateId) ? (string) $mandateId : null);

            return $response;
        } catch (Throwable $exception) {
            $this->sync->markFailed($record, $exception);

            throw $exception;
        }
    }
}
