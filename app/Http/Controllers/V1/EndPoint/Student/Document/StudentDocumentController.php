<?php
namespace App\Http\Controllers\V1\EndPoint\Student\Document;

use App\Http\Controllers\Controller;
use App\Models\DocumentEvaluation;
use App\Models\Media\StorageMedia;
use App\Models\Roles\Student\User\Student;
use App\Models\StudentDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $this->student($request); $documents = $this->documentsFor($student); $required = $this->requiredFor($student);
        return response()->json(['summary' => [
            'total' => count($documents),
            'valid' => count(array_filter($documents, fn ($d) => $d['status'] === 'valid')),
            'pending' => count(array_filter($documents, fn ($d) => $d['status'] === 'pending')) + count(array_filter($required, fn ($d) => $d['state'] === 'pending')),
            'expiring' => count(array_filter($documents, fn ($d) => $d['status'] === 'expiring')),
        ], 'documents' => array_map(fn ($document) => $this->publicDocument($document), $documents), 'required_documents' => $required]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['files' => ['required','array','min:1','max:10'], 'files.*' => ['required','file','mimes:pdf,jpg,jpeg,png','max:10240']]);
        $student = $this->student($request);
        foreach ($data['files'] as $file) {
            $path = $file->store("student-documents/{$student->id}", 'public');
            $stored = StorageMedia::create(['user_id' => $request->user()->id, 'path' => $path, 'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 'type' => strtolower($file->getClientOriginalExtension()), 'is_active' => true]);
            $student->media()->create(['user_id' => $request->user()->id, 'storage_media_id' => $stored->id]);
        }
        return response()->json(['message' => 'Document(s) importé(s) avec succès.'], 201);
    }

    public function download(Request $request, string $documentId)
    {
        $document = collect($this->documentsFor($this->student($request)))->firstWhere('id', $documentId);
        abort_unless($document && $document['downloadable'], 404);
        $disk = $document['storage_disk'] ?? 'public';
        if ($request->boolean('preview')) return Storage::disk($disk)->response($document['storage_path'], $document['filename'], ['Content-Disposition' => 'inline; filename="' . $document['filename'] . '"']);
        return Storage::disk($disk)->download($document['storage_path'], $document['filename']);
    }

    public function destroy(Request $request, string $documentId): JsonResponse
    {
        $student = $this->student($request);
        $document = collect($this->documentsFor($student))->firstWhere('id', $documentId);
        abort_unless($document && ($document['deletable'] ?? false), 403, 'Ce document ne peut pas être supprimé.');

        if ($document['source'] === 'media') {
            $media = $student->media()->with('storageMedia')->findOrFail($documentId);
            $storage = $media->storageMedia;
            $media->delete();
            if ($storage && $storage->media()->count() === 0) {
                Storage::disk('public')->delete($storage->path);
                $storage->delete();
            }
        }

        if ($document['source'] === 'required') {
            $documents = $student->required_documents ?? [];
            $files = $documents[$document['required_type']] ?? [];
            $files = isset($files['path']) ? [$files] : (is_array($files) ? $files : []);
            $path = $document['required_path'];
            $documents[$document['required_type']] = array_values(array_filter($files, fn ($file) => !is_array($file) || ($file['path'] ?? null) !== $path));
            if (!$documents[$document['required_type']]) unset($documents[$document['required_type']]);
            $student->required_documents = $documents;
            $student->save();
            Storage::disk($document['storage_disk'] ?? 'public')->delete($path);
        }

        return response()->json(['message' => 'Document supprimé avec succès.']);
    }

    private function student(Request $request): Student
    {
        $actor = $request->user();
        $studentId = $request->input('student_id');
        if ($studentId && $actor?->hasAnyRole(['admin', 'super-admin', 'secretary'])) return Student::query()->findOrFail($studentId);
        $student = $actor?->student; abort_unless($student, 404, 'Profil élève introuvable.'); return $student;
    }

    private function documentsFor(Student $student): array
    {
        if ($student->registration_status !== 'approved') {
            return [];
        }

        $documents = [];
        // The registration is approved, so every submitted document is now
        // available in the candidate's document page.
        $registrationApproved = true;
        $reviews = StudentDocument::query()->where('student_id', $student->id)->get();
        foreach (($student->required_documents ?? []) as $title => $files) {
            $review = $this->approvedReviewFor($reviews, (string) $title);
            if (!$registrationApproved && !$review) continue;
            $files = isset($files['path']) ? [$files] : (is_array($files) ? $files : []);
            foreach ($files as $index => $file) {
                if (!is_array($file) || empty($file['path'])) continue;
                $disk = $file['disk'] ?? 'public';
                $document = $this->document("required-{$title}-{$index}", $file['name'] ?? $title, $file['mime'] ?? pathinfo($file['path'], PATHINFO_EXTENSION), $file['uploaded_at'] ?? null, 'valid', $file['path'], $file['name'] ?? basename($file['path']), $file['expires_at'] ?? null, $disk);
                $document['source'] = 'required'; $document['deletable'] = true; $document['required_type'] = $title; $document['required_path'] = $file['path']; $document['size'] = $file['size'] ?? null;
                $documents[] = $document;
            }
        }
        return $documents;
    }

    private function requiredFor(Student $student): array
    {
        if ($student->registration_status !== 'approved') {
            return [];
        }

        return collect($student->required_documents ?? [])->map(function ($files, $title) {
            $files = isset($files['path']) ? [$files] : (is_array($files) ? $files : []);
            $file = collect($files)->filter(fn ($f) => is_array($f) && !empty($f['path']))->last(); $state = $file ? $this->status($file['status'] ?? null, $file['expires_at'] ?? null) : 'pending';
            return ['id' => (string) $title, 'title' => $title, 'state' => $state, 'detail' => $state === 'valid' ? 'Document valide' : ($state === 'expiring' ? 'À renouveler' : 'En attente de réception')];
        })->values()->all();
    }

    private function document(string $id, string $title, ?string $type, $addedAt, string $status, string $path, string $filename, ?string $expiresAt = null, string $disk = 'public'): array
    {
        $expires = $expiresAt ? Carbon::parse($expiresAt) : null; $category = $this->category($title);
        return ['id'=>$id, 'title'=>$title, 'category'=>$category, 'type'=>strtoupper(str_replace('image/', '', $type ?: pathinfo($path, PATHINFO_EXTENSION) ?: 'file')), 'added_at'=>$addedAt ? Carbon::parse($addedAt)->format('d/m/Y') : '—', 'status'=>$status, 'status_label'=>$status === 'valid' ? 'À jour' : ($status === 'expiring' ? 'Expirant bientôt' : 'En attente'), 'tone'=>$status === 'valid' ? 'green' : 'amber', 'icon'=>$this->icon($category), 'iconTone'=>$status === 'valid' ? 'green' : 'amber', 'expires_at'=>$expires?->toDateString(), 'expiresAt'=>$expires?->format('d/m/Y'), 'warning'=>$status === 'expiring', 'download_url'=>"/student/documents/{$id}/download", 'downloadable'=>Storage::disk($disk)->exists($path), 'storage_path'=>$path, 'storage_disk'=>$disk, 'filename'=>$filename];
    }

    private function approvedReviewFor($reviews, string $documentType): ?StudentDocument
    {
        $type = $this->normalise($documentType);
        return $reviews->first(fn (StudentDocument $review) => $this->normalise($review->document_type) === $type
            || str_starts_with($type, $this->normalise($review->document_type)));
    }

    private function normalise(string $value): string
    {
        return str_replace([' ', "'", '’', '-', '_'], '', mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $value)));
    }

    private function status(?string $status, ?string $expiresAt): string { if ($expiresAt && Carbon::parse($expiresAt)->between(now(), now()->addDays(30))) return 'expiring'; return in_array($status, ['valid','pending','expiring','expired'], true) ? $status : 'valid'; }
    private function publicDocument(array $document): array { unset($document['storage_path'], $document['storage_disk'], $document['filename'], $document['source'], $document['required_type'], $document['required_path']); return $document; }
    private function category(string $title): string { $title = mb_strtolower($title); if (str_contains($title, 'médical') || str_contains($title, 'medical')) return 'Médical'; if (str_contains($title, 'examen') || str_contains($title, 'assr')) return 'Examen'; if (str_contains($title, 'livret') || str_contains($title, 'formation')) return 'Pédagogique'; return 'Administratif'; }
    private function icon(string $category): string { return match ($category) {'Médical'=>'medical','Pédagogique'=>'book','Examen'=>'exam',default=>'file'}; }
}
