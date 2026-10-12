<?php

namespace App\Http\Controllers\V1\EndPoint\Student;

use App\Http\Controllers\Controller;
use App\Models\DocumentEvaluation;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\Rating;
use App\Notifications\V1\Monitor\Training\StudentTrainingBookletNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentFormationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()?->student;
        if (!$student) return response()->json(['message' => 'Élève introuvable.'], 404);

        $lessons = $this->lessons($student->id);
        $ratings = Rating::query()->whereIn('reservation_id', $lessons->pluck('id'))
            ->with(['competency', 'evaluationDetail'])->get()->groupBy('reservation_id');

        return response()->json(['data' => [
            'student' => ['id' => (string) $student->id, 'name' => $student->user?->name ?? 'Élève'],
            'lessons' => $lessons->values()->map(fn (Reservation $lesson, int $index) => $this->lesson($lesson, $lessons->count() - $index, $ratings->get($lesson->id, collect())))->values(),
        ]]);
    }

    public function schema(Request $request, Reservation $reservation): JsonResponse
    {
        $student = $request->user()?->student;
        if (!$student || (string) $reservation->training?->student_id !== (string) $student->id) return response()->json(['message' => 'Schéma introuvable.'], 404);

        $reservation->load('evaluation');
        $schema = $reservation->evaluation?->data['formation']['schema'] ?? null;
        return response()->json(['data' => ['schema' => is_array($schema) ? $schema : null, 'editable' => true]]);
    }

    public function updateSchema(Request $request, Reservation $reservation): JsonResponse
    {
        $student = $request->user()?->student;
        if (!$student || (string) $reservation->training?->student_id !== (string) $student->id) {
            return response()->json(['message' => 'Schéma introuvable.'], 404);
        }

        $schema = $this->validatedSchema($request);

        $reservation->load('evaluation');
        $data = is_array($reservation->evaluation?->data) ? $reservation->evaluation->data : [];
        $data['formation'] = array_merge(is_array($data['formation'] ?? null) ? $data['formation'] : [], ['schema' => $schema]);
        DocumentEvaluation::updateOrCreate(
            ['student_id' => $student->id, 'reservation_id' => $reservation->id],
            ['monitor_id' => $reservation->monitor_id, 'data' => $data],
        );

        return response()->json(['data' => ['schema' => $schema, 'editable' => true]]);
    }
    public function sendBooklet(Request $request): JsonResponse
    {
        $student = $request->user()?->student;
        if (!$student) return response()->json(['message' => 'Élève introuvable.'], 404);

        $student->load('user');
        abort_if(blank($student->user?->email), 422, 'Votre adresse e-mail est requise pour recevoir le livret.');
        $lessons = $this->lessons($student->id);
        abort_if($lessons->isEmpty(), 422, 'Aucune leçon n’est disponible pour générer votre livret.');

        $sender = $lessons->first()?->monitor?->user ?? $student->user;
        Notification::route('mail', $student->user->email)->notify(new StudentTrainingBookletNotification($sender, $student, $lessons));

        return response()->json(['data' => ['message' => 'Votre livret de formation a été envoyé à votre adresse e-mail.', 'lesson_count' => $lessons->count()]]);
    }

    private function validatedSchema(Request $request): array
    {
        $schema = $request->validate(['schema' => ['required', 'array']])['schema'];

        Validator::make(['schema' => $schema], [
            'schema.version' => ['required', 'integer', 'in:1'],
            'schema.canvas' => ['required', 'array'],
            'schema.canvas.width' => ['required', 'integer', 'between:320,2000'],
            'schema.canvas.height' => ['required', 'integer', 'between:240,2000'],
            'schema.strokes' => ['present', 'array', 'max:250'],
            'schema.strokes.*.color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'schema.strokes.*.width' => ['required', 'numeric', 'between:1,30'],
            'schema.strokes.*.points' => ['required', 'array', 'min:2', 'max:1000'],
            'schema.strokes.*.points.*.x' => ['required', 'numeric', 'between:0,1000'],
            'schema.strokes.*.points.*.y' => ['required', 'numeric', 'between:0,1000'],
            'schema.objects' => ['present', 'array', 'max:100'],
            'schema.objects.*.id' => ['required', 'string', 'max:80'],
            'schema.objects.*.type' => ['required', Rule::in(['car-yellow', 'car-red', 'car-blue', 'rider-purple', 'rider-dark', 'text'])],
            'schema.objects.*.x' => ['required', 'numeric', 'between:0,1000'],
            'schema.objects.*.y' => ['required', 'numeric', 'between:0,1000'],
            'schema.objects.*.width' => ['required', 'numeric', 'between:25,250'],
            'schema.objects.*.height' => ['required', 'numeric', 'between:25,250'],
            'schema.objects.*.rotation' => ['required', 'numeric', 'between:-3600,3600'],
            'schema.objects.*.text' => ['nullable', 'string', 'max:200'],
            'schema.background' => ['nullable', 'array'],
            'schema.background.id' => ['nullable', 'string', 'max:80'],
            'schema.background.asset' => ['nullable', 'string', 'max:240', 'regex:/^road-backgrounds\/[a-z0-9-]+\.(?:svg|jpg)$/'],
            'schema.background.src' => ['nullable', 'string', 'max:1000000', 'regex:/^data:image\/(?:png|jpeg|webp);base64,/i'],
            'schema.background.name' => ['nullable', 'string', 'max:160'],
            'schema.background.width' => ['nullable', 'integer', 'between:320,2000'],
            'schema.background.height' => ['nullable', 'integer', 'between:240,2000'],
        ])->validate();

        if (strlen((string) json_encode($schema)) > 1100000) {
            throw ValidationException::withMessages(['schema' => 'Le schéma est trop volumineux. Utilisez une image de fond plus légère.']);
        }

        return $schema;
    }
    private function lessons(string $studentId)
    {
        return Reservation::query()
            ->whereHas('training', fn ($query) => $query->where('student_id', $studentId))
            ->with(['training.offer', 'monitor.user', 'monitor.details', 'lieu', 'evaluation', 'reviewMonitor'])
            ->orderByDesc('date')->orderByDesc('start_at')->get();
    }

    private function lesson(Reservation $reservation, int $number, $ratings): array
    {
        $data = is_array($reservation->evaluation?->data) ? $reservation->evaluation->data : [];
        $meta = is_array($data['formation'] ?? null) ? $data['formation'] : [];
        $date = $reservation->date instanceof Carbon ? $reservation->date : Carbon::parse($reservation->date);
        $duration = (int) ($reservation->duration_minutes ?: ((int) $reservation->hour * 60));
        $signed = $data['formation_signature'] ?? null;
        $absent = (bool) ($meta['is_absent'] ?? $reservation->reviewMonitor?->is_absent ?? false);
        $past = Carbon::parse($date->format('Y-m-d').' '.$this->time($reservation->end_at))->isPast();
        $maneuverCounts = $meta['maneuver_counts'] ?? [];

        return [
            'id' => (string) $reservation->id, 'number' => $number, 'date' => $date->format('Y-m-d'),
            'duration_minutes' => $duration, 'start_at' => $this->time($reservation->start_at),
            'monitor_name' => $reservation->monitor?->user?->name ?? '—',
            'department' => $meta['department'] ?? $reservation->monitor?->details?->departement ?? '—',
            'type' => $reservation->training?->session_type ?: 'Prestation', 'offer_name' => $reservation->training?->offer?->name ?? '—',
            'place_name' => $reservation->lieu?->name ?? '—',
            'boite_auto' => (bool) ($meta['boite_auto'] ?? $reservation->training?->offer?->is_auto ?? false),
            'evaluation_initiale' => $duration === 60 && (bool) ($data['evaluation_initiale'] ?? false),
            'evaluation_permis' => (bool) ($data['evaluation_permis'] ?? false),
            'public_observation' => $data['public_observations'] ?? '',
            'maneuvers' => array_values((array) ($data['manoeuvres'] ?? $data['maneuvers'] ?? [])),
            'maneuver_counts' => is_array($maneuverCounts) ? $maneuverCounts : [], 'has_schema' => is_array($meta['schema'] ?? null),
            'is_absent' => $absent, 'status' => $signed ? 'signed' : ($absent ? 'absent' : ($past ? 'completed' : 'upcoming')), 'signature' => $signed,
            'competencies' => $ratings->map(fn (Rating $rating) => ['id' => (string) $rating->competency_id, 'title' => $rating->competency?->label ?? 'Compétence', 'rating' => (int) $rating->rating, 'observations' => $rating->comment ?? ''])->values(),
        ];
    }

    private function time(mixed $value): string
    {
        return $value instanceof Carbon ? $value->format('H:i') : (string) ($value ?? '—');
    }
}
