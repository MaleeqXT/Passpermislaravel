<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Document;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Models\StudentDocument;
use App\Notifications\V1\Student\Welcome\RegistrationConfirmedNotification;
use App\Notifications\V1\Student\Welcome\DocumentStatusUpdatedNotification;
use App\Notifications\V1\Student\Welcome\RegistrationStatusUpdatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminStudentDocumentController extends Controller
{
    private const REQUIREMENTS = [
        'sans_neph' => [
            ['type' => "Carte d'identité", 'required' => true],
            ['type' => 'Justificatif de domicile', 'required' => true],
            ['type' => 'ASSR2', 'required' => false, 'note' => 'À préciser lors de l’appel'],
            ['type' => "Carte d'identité du représentant légal", 'required' => false, 'note' => "Si l'élève vit chez ses parents"],
            ['type' => "Attestation d'hébergement", 'required' => false, 'note' => 'Datée et signée par les 2 personnes'],
            ['type' => 'Ephotos', 'required' => false],
        ],
        'avec_neph' => [
            ['type' => 'Cerfa 02', 'required' => true],
            ['type' => 'Feuille de code', 'required' => true],
        ],
    ];

    public function index(): JsonResponse
    {
        // Show only registrations that include selected registration documents.
        // Dashboard uploads are managed separately and must not create entries
        // in this approval queue.
        $students = Student::query()
            ->whereNotNull('required_documents')
            ->whereRaw('JSON_LENGTH(required_documents) > 0')
            ->with('user:id,first_name,last_name,name,email')->latest()->get()
            ->map(function (Student $student) {
                $documents = $this->documents($student);
                return [
                    'id' => $student->id,
                    'student' => $this->studentName($student),
                    'email' => $student->user?->email,
                    'neph_status' => $student->neph_status ?: 'sans_neph',
                    // Count only files actually selected and stored by the
                    // student, not every possible NEPH requirement.
                    'documents_count' => count(array_filter($documents, fn ($document) => $document['uploaded'])),
                    'approved_documents_count' => count(array_filter($documents, fn ($document) => $document['uploaded'] && $document['status'] === 'approved')),
                    'required_count' => count(array_filter($documents, fn ($document) => $document['required'])),
                    'approved_required_count' => count(array_filter($documents, fn ($document) => $document['required'] && $document['status'] === 'approved')),
                    'registration_status' => $student->registration_status ?: 'pending',
                    'document_status' => $this->overallStatus($documents),
                ];
            })->values();

        return response()->json(['students' => $students]);
    }

    public function show(Student $student): JsonResponse
    {
        $student->load('user:id,first_name,last_name,name,email');
        return response()->json(['student' => [
            'id' => $student->id,
            'name' => $this->studentName($student),
            'email' => $student->user?->email,
            'neph_status' => $student->neph_status ?: 'sans_neph',
            'registration_confirmation_sent_at' => $student->registration_confirmation_sent_at?->toISOString(),
        ], 'documents' => $this->documents($student)]);
    }

    public function updateStudentStatus(Request $request, Student $student): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,rejected']]);
        $wasApproved = $student->registration_status === 'approved';
        $student->update(['registration_status' => $data['status']]);

        // The registration decision is independent of document decisions.
        // When an admin approves the student from the list, notify them now.
        if ($data['status'] === 'approved' && !$wasApproved) {
            // An earlier approval may have happened while the app was using
            // the log mailer. A new approval transition must be able to send
            // through the currently configured SMTP transport.
            $student->forceFill(['registration_confirmation_sent_at' => null])->save();
            $this->sendRegistrationConfirmation($student);
        }

        if (in_array($data['status'], ['pending', 'rejected'], true)) {
            $this->sendRegistrationStatusNotification($student, $data['status']);
        }

        return response()->json(['registration_status' => $student->registration_status]);
    }

    public function update(Request $request, Student $student, string $documentType): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,rejected']]);
        $document = collect($this->documents($student))->firstWhere('type', $documentType);
        abort_unless($document, 404, 'Type de document inconnu.');
        abort_unless($document['file_path'], 422, 'Le document doit être téléversé avant sa validation.');

        $review = StudentDocument::query()->updateOrCreate(
            ['student_id' => $student->id, 'document_type' => $documentType],
            [
                'file_path' => $document['file_path'],
                'original_name' => $document['file_name'],
                'file_size' => $document['file_size'],
                'required' => $document['required'],
                'status' => $data['status'],
                'approved_at' => $data['status'] === 'approved' ? now() : null,
                'rejected_at' => $data['status'] === 'rejected' ? now() : null,
            ]
        );

        // Each document is reviewed independently. Inform the student on
        // every Pending, Approved, or Rejected status change.
        $this->sendDocumentStatusNotification($student, $documentType, $data['status']);

        if ($data['status'] === 'approved') {
            $this->sendConfirmationWhenReady($student);
        }

        return response()->json(['document' => $this->document($student, $document['definition'], $review)]);
    }

    public function download(Request $request, Student $student, string $documentType)
    {
        $document = collect($this->documents($student))->firstWhere('type', $documentType);
        $disk = $document['file_disk'] ?? 'public';
        abort_unless($document && $document['file_path'] && Storage::disk($disk)->exists($document['file_path']), 404);
        return Storage::disk($disk)->response($document['file_path'], $document['file_name'] ?: basename($document['file_path']), [
            'Content-Disposition' => 'inline; filename="' . ($document['file_name'] ?: basename($document['file_path'])) . '"',
        ]);
    }

    private function documents(Student $student): array
    {
        $reviews = StudentDocument::query()->where('student_id', $student->id)->get()->keyBy('document_type');
        $requirements = $this->requirements($student);
        $documents = [];

        // Return only the document categories the student selected during
        // registration. Do not add empty checklist rows for documents they did
        // not choose.
        foreach (array_keys($student->required_documents ?? []) as $uploadedType) {
            $isRequired = collect($requirements)->contains(
                fn (array $definition) => $this->documentMatches($definition['type'], (string) $uploadedType)
            );
            $definition = ['type' => (string) $uploadedType, 'required' => $isRequired];
            $documents[] = $this->document($student, $definition, $reviews->get($definition['type']));
        }

        return $documents;
    }

    private function document(Student $student, array $definition, ?StudentDocument $review): array
    {
        $file = $this->fileFor($student, $definition['type']);
        $filePath = $file['path'] ?? $review?->file_path;
        return [
            'type' => $definition['type'],
            'required' => $definition['required'],
            'note' => $definition['note'] ?? null,
            'status' => $review?->status ?? 'pending',
            'uploaded' => (bool) $filePath,
            'file_name' => $file['name'] ?? $review?->original_name,
            'file_path' => $filePath,
            'file_disk' => $file['disk'] ?? 'public',
            'file_size' => $file['size'] ?? $review?->file_size,
            'uploaded_at' => $file['uploaded_at'] ?? null,
            'approved_at' => $review?->approved_at?->toISOString(),
            'rejected_at' => $review?->rejected_at?->toISOString(),
            'definition' => $definition,
        ];
    }

    private function requirements(Student $student): array
    {
        return self::REQUIREMENTS[$student->neph_status] ?? self::REQUIREMENTS['sans_neph'];
    }

    private function fileFor(Student $student, string $documentType): ?array
    {
        $documents = $student->required_documents ?? [];
        $key = collect(array_keys($documents))->first(fn ($key) => $this->documentMatches($documentType, $key));
        if (!$key) return null;
        $files = $documents[$key];
        $files = isset($files['path']) ? [$files] : (is_array($files) ? $files : []);
        return collect($files)->filter(fn ($file) => is_array($file) && !empty($file['path']))->last();
    }

    private function normalise(string $value): string
    {
        return str_replace([' ', "'", '’', '-', '_'], '', mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $value)));
    }

    private function documentMatches(string $requiredType, string $uploadedType): bool
    {
        $required = $this->normalise($requiredType);
        $uploaded = $this->normalise($uploadedType);
        if ($required === $uploaded || str_starts_with($uploaded, $required)) return true;

        return match ($required) {
            'cartedidentite' => str_contains($uploaded, 'piecedidentite'),
            'justificatifdedomicile' => str_contains($uploaded, 'facture') || str_contains($uploaded, 'domicile'),
            'attestationdhebergement' => str_contains($uploaded, 'attestationdhebergement'),
            'cartedidentitedurepresentantlegal' => str_contains($uploaded, 'hebergeur'),
            'feuilledecode' => $uploaded === 'code',
            default => false,
        };
    }

    private function overallStatus(array $documents): string
    {
        $required = array_filter($documents, fn ($document) => $document['required']);
        return $required && count(array_filter($required, fn ($document) => $document['status'] === 'approved')) === count($required)
            ? 'approved' : 'pending';
    }

    private function studentName(Student $student): string
    {
        return trim(($student->user?->first_name ?? '') . ' ' . ($student->user?->last_name ?? '')) ?: ($student->user?->name ?? 'Élève');
    }

    private function sendConfirmationWhenReady(Student $student): void
    {
        $student->refresh();
        if ($student->registration_confirmation_sent_at || $this->overallStatus($this->documents($student)) !== 'approved') return;

        $this->sendRegistrationConfirmation($student);
    }

    private function sendRegistrationConfirmation(Student $student): void
    {
        $student->refresh();
        if ($student->registration_confirmation_sent_at) return;

        try {
            $student->loadMissing('user');
            if (!$student->user?->email) {
                Log::warning('Student registration confirmation has no recipient email.', ['student_id' => $student->id]);
                return;
            }

            $student->user->notify(new RegistrationConfirmedNotification());
            $student->forceFill(['registration_confirmation_sent_at' => now()])->save();
        } catch (\Throwable $exception) {
            Log::error('Unable to send student registration confirmation.', ['student_id' => $student->id, 'exception' => $exception]);
        }
    }

    private function sendDocumentStatusNotification(Student $student, string $documentType, string $status): void
    {
        try {
            $student->loadMissing('user');
            if (!$student->user?->email) {
                Log::warning('Document status notification has no recipient email.', ['student_id' => $student->id]);
                return;
            }

            $student->user->notify(new DocumentStatusUpdatedNotification($documentType, $status));
        } catch (\Throwable $exception) {
            // Do not roll back the admin's document decision if mail fails.
            Log::error('Unable to send document status notification.', [
                'student_id' => $student->id,
                'document_type' => $documentType,
                'status' => $status,
                'exception' => $exception,
            ]);
        }
    }

    private function sendRegistrationStatusNotification(Student $student, string $status): void
    {
        try {
            $student->loadMissing('user');
            if (!$student->user?->email) return;
            $student->user->notify(new RegistrationStatusUpdatedNotification($status));
        } catch (\Throwable $exception) {
            Log::error('Unable to send registration status notification.', [
                'student_id' => $student->id,
                'status' => $status,
                'exception' => $exception,
            ]);
        }
    }
}
