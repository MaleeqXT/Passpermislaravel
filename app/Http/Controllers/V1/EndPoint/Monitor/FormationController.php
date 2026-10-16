<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor;

use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\DocumentEvaluation;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\Rating;
use App\Models\Roles\Student\User\Student;
use App\Notifications\V1\Monitor\Training\StudentTrainingBookletNotification;
use App\Services\SatisfactionOfferTriggerService;
use App\Services\DrivingLessonMilestoneService;
use App\Services\Student\Training\FormationWalletMinutesService;
use App\Services\Monitor\Training\StudentTrainingBookletPdfService;
use App\Services\Student\Training\info\TrainingInterface;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormationController extends Controller
{
    private const DURATION_OPTIONS = [45, 60, 75, 90, 105, 120, 150, 180, 210, 240, 270, 300];

    private const DURATION_LABELS = [
        45 => '45 minutes', 60 => '1 heure', 75 => '1h 15min', 90 => '1h 30min',
        105 => '1h 45min', 120 => '2 heures', 150 => '2h 30min', 180 => '3 heures',
        210 => '3h 30min', 240 => '4 heures', 270 => '4h 30min', 300 => '5 heures',
    ];

    private const MANEUVERS = [
        'epi_droit' => 'Épi à droite', 'epi_gauche' => 'Épi à gauche',
        'bataille_droit' => 'Bataille à droite', 'bataille_gauche' => 'Bataille à gauche',
        'creneau_droit' => 'Créneau droit', 'creneau_gauche' => 'Créneau gauche',
        'marche_arriere' => 'Marche arrière', 'arriere_courbe' => 'Arrière courbe',
        'demi_tour' => 'Demi tour', 'demarrage_cote' => 'Démarrage en côte',
        'giratoire' => 'Giratoire', 'arret_precision' => 'Arrêt de précision',
    ];

    public function index(Request $request, Student $student)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $lessons = $this->studentLessons($student)
            ->with(['training.offer', 'evaluation', 'reviewMonitor', 'monitor.user', 'monitor.details'])
            ->orderBy('date')->orderBy('start_at')->get();
        $ratings = Rating::whereIn('reservation_id', $lessons->pluck('id'))
            ->with(['competency', 'evaluationDetail'])->get()->groupBy('reservation_id');
        $department = $this->department($monitor);
        $offers = $student->wallets()->where('status', WalletTypeEnum::ACTIVE->value)->with('offer')->get()
            ->filter(fn ($wallet) => $wallet->offer)->map(fn ($wallet) => [
                'id' => $wallet->offer_id,
                'name' => $wallet->offer->name,
                'balance_minutes' => $this->walletMinutes()->minutesFor($wallet),
            ])->unique('id')->values();

        return response()->json(['data' => [
            'lessons' => $lessons->map(fn ($lesson, $index) => $this->payload($lesson, $index + 1, $ratings->get($lesson->id, collect()), $monitor))->reverse()->values(),
            'options' => [
                'instructors' => [['id' => $monitor->id, 'name' => $monitor->user?->name ?? 'Moniteur', 'department' => $department]],
                'departments' => $department ? [$department] : [],
                'places' => $monitor->lieux()->get()->map(fn ($place) => ['id' => $place->id, 'name' => $place->name]),
                'offers' => $offers,
                'types' => $lessons->map(fn ($lesson) => $lesson->training?->session_type)->filter()->prepend('Prestation')->unique()->values(),
                'durations' => collect(self::DURATION_OPTIONS)->map(fn ($minutes) => ['minutes' => $minutes, 'label' => self::DURATION_LABELS[$minutes]])->values(),
                'can_sign' => (string) $request->user()?->monitor?->id === (string) $monitor->id,
            ],
        ]]);
    }

    public function sendBooklet(Request $request, Student $student)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $email = $monitor->user?->email;
        abort_if(blank($email), 422, 'Votre adresse e-mail est requise pour recevoir le livret.');

        $lessons = $this->bookletLessons($student);

        Notification::route('mail', $email)->notify(
            new StudentTrainingBookletNotification($monitor->user, $student, $lessons)
        );

        return response()->json(['data' => [
            'message' => 'Le livret de formation a été envoyé à votre adresse e-mail.',
            'lesson_count' => $lessons->count(),
        ]]);
    }
    public function downloadBooklet(Request $request, Student $student, StudentTrainingBookletPdfService $booklet)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $pdf = $booklet->output($student, $this->bookletLessons($student));

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$booklet->filename($student).'"',
        ]);
    }

    /**
     * The contract dashboard stores the initial evaluation in the same
     * DocumentEvaluation JSON record as Formation. These endpoints expose
     * that canonical record without creating a second fiche system.
     */
    public function initialEvaluation(Request $request, Student $student)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $reservation = $this->initialEvaluationReservation($monitor, $student);

        return response()->json(['data' => $this->initialEvaluationPayload($student, $reservation)]);
    }

    public function updateInitialEvaluation(Request $request, Student $student)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $reservation = $this->initialEvaluationReservation($monitor, $student);
        $values = $this->validatedInitialEvaluation($request);

        $payload = DB::transaction(function () use ($reservation, $student, $monitor, $values) {
            $locked = Reservation::whereKey($reservation->id)->with('evaluation')->lockForUpdate()->firstOrFail();
            abort_if($this->isCancelled($locked), 422, 'Une évaluation liée à une leçon annulée ne peut pas être modifiée.');
            abort_if(isset($locked->evaluation?->data['formation_signature']), 423, 'Cette évaluation est verrouillée car la leçon associée est signée.');

            $data = $locked->evaluation?->data ?? [];
            // Preserve Formation schema, observations, and all unknown legacy
            // keys while replacing only the canonical evaluation sections.
            $data = array_replace($data, $values['data']);
            $data['evaluation_initiale'] = true;

            DocumentEvaluation::updateOrCreate(
                ['student_id' => $student->id, 'reservation_id' => $locked->id],
                ['monitor_id' => $monitor->id, 'data' => $data],
            );

            return $locked->fresh(['evaluation', 'training']);
        });

        return response()->json(['data' => $this->initialEvaluationPayload($student->fresh('user'), $payload)]);
    }

    /**
     * This reuses the existing contract document. The monitor may only open
     * it for a linked student once the existing contract eligibility rule is met.
     */
    public function initialEvaluationContract(Request $request, Student $student)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        abort_unless($student->canViewContract(), 422, 'Le contrat sera disponible après la deuxième leçon réservée.');

        $student->load('user');

        return response()->view('pdf.normal.contract-formation-html', [
            'student' => $student,
            'isAdmin' => true,
        ]);
    }
    /**
     * The question wizard belongs to the same initial-evaluation record as the
     * fiche. Keeping it here prevents a second, disconnected student dossier.
     */
    public function initialEvaluationQuestions(Request $request, Student $student)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $reservation = $this->initialEvaluationReservation($monitor, $student);

        return response()->json(['data' => $this->initialEvaluationQuestionsPayload($student, $reservation)]);
    }

    public function updateInitialEvaluationQuestions(Request $request, Student $student)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $reservation = $this->initialEvaluationReservation($monitor, $student);
        $values = $this->validatedInitialEvaluationQuestions($request);

        $updated = DB::transaction(function () use ($reservation, $student, $monitor, $values) {
            $locked = Reservation::whereKey($reservation->id)->with('evaluation', 'training')->lockForUpdate()->firstOrFail();
            abort_if($this->isCancelled($locked), 422, 'Une évaluation liée à une leçon annulée ne peut pas être modifiée.');
            abort_if(isset($locked->evaluation?->data['formation_signature']), 423, 'Cette évaluation est verrouillée car la leçon associée est signée.');

            $data = $locked->evaluation?->data ?? [];
            $answers = $values['answers'] ?? [];
            $final = $values['final'] ?? [];
            $data['initial_evaluation_questions'] = [
                'version' => 1,
                'answers' => $answers,
                'current_question' => $values['current_question'] ?? 1,
                'status' => ($values['validated'] ?? false) ? 'validated' : 'in_progress',
                'final' => $final,
                'updated_at' => now()->toIso8601String(),
            ];
            $this->mapInitialEvaluationQuestionAnswers($data, $answers, $final);
            $data['evaluation_initiale'] = true;

            DocumentEvaluation::updateOrCreate(
                ['student_id' => $student->id, 'reservation_id' => $locked->id],
                ['monitor_id' => $monitor->id, 'data' => $data],
            );

            return $locked->fresh(['evaluation', 'training']);
        });

        return response()->json(['data' => $this->initialEvaluationQuestionsPayload($student->fresh('user'), $updated)]);
    }
    public function schema(Request $request, Student $student, Reservation $reservation)
    {
        $monitor = $this->monitor($request);
        $this->authorizeLesson($monitor, $student, $reservation);
        $data = $reservation->evaluation?->data ?? [];

        return response()->json(['data' => [
            'schema' => $this->schemaFromData($data),
            'editable' => !$this->isCancelled($reservation),
        ]]);
    }

    public function updateSchema(Request $request, Student $student, Reservation $reservation)
    {
        $monitor = $this->monitor($request);
        $this->authorizeLesson($monitor, $student, $reservation);
        $schema = $this->validatedSchema($request);

        DB::transaction(function () use ($reservation, $student, $monitor, $schema) {
            $this->lockSchemaEditable($reservation);
            $data = $reservation->evaluation?->data ?? [];
            $data['formation'] = array_merge($data['formation'] ?? [], ['schema' => $schema]);
            DocumentEvaluation::updateOrCreate(
                ['student_id' => $student->id, 'reservation_id' => $reservation->id],
                ['monitor_id' => $monitor->id, 'data' => $data],
            );
        });

        return response()->json(['data' => [
            'schema' => $schema,
            'saved_at' => now()->toIso8601String(),
        ]]);
    }
    public function store(Request $request, Student $student, ReservationInterface $planning, TrainingInterface $trainingService)
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);
        $values = $this->validated($request, $monitor);
        if (Carbon::parse($values['date'].' '.$values['start_at'])->isPast()) {
            throw ValidationException::withMessages(['date' => 'Une nouvelle leçon doit être programmée dans le futur.']);
        }

        [$reservation, $milestoneAlert] = DB::transaction(function () use ($values, $monitor, $student, $planning, $trainingService) {
            $this->walletMinutes()->assertAvailable($student, $values['offer_id'], $values['duration_minutes']);
            Monitor::whereKey($monitor->id)->lockForUpdate()->firstOrFail();
            $this->checkConflicts($monitor, $student, $values);
            $reservation = $planning->create($this->planningValues($values, $monitor));
            $trainingService->create([
                'student_id' => $student->id,
                'offer_id' => $values['offer_id'],
                'session_type' => $values['type'],
                'duration_minutes' => $values['duration_minutes'],
            ], $reservation);
            $this->writeEvaluation($reservation, $student, $monitor, $values);
            $milestoneAlert = app(DrivingLessonMilestoneService::class)->forUpcomingLesson($student, $reservation);

            return [$reservation, $milestoneAlert];
        });

        return response()->json([
            'data' => $this->payload($reservation->fresh(['training.offer', 'evaluation', 'reviewMonitor'])),
            'milestone_alert' => app(DrivingLessonMilestoneService::class)->payload($milestoneAlert),
        ], 201);
    }

    public function update(Request $request, Student $student, Reservation $reservation, ReservationInterface $planning)
    {
        $monitor = $this->monitor($request);
        $this->authorizeLesson($monitor, $student, $reservation);
        $values = $this->validated($request, $monitor);

        $milestoneAlert = DB::transaction(function () use ($reservation, $student, $monitor, $values, $planning) {
            $this->lockEditable($reservation);
            $previous = $reservation->training;
            $previousMinutes = $this->durationMinutes($reservation);
            $scheduleChanged = $reservation->date->toDateString() !== $values['date']
                || $reservation->start_at->format('H:i') !== $values['start_at']
                || $previousMinutes !== $values['duration_minutes']
                || $previous->offer_id !== $values['offer_id']
                || $reservation->lieu_id !== $values['lieu_id'];

            if ($scheduleChanged) {
                if ($this->hasEnded($reservation) || Carbon::parse($values['date'].' '.$values['start_at'])->isPast()) {
                    throw ValidationException::withMessages(['date' => 'Les horaires et le forfait d’une leçon passée ne peuvent pas être modifiés.']);
                }

                $sameOffer = $previous->offer_id === $values['offer_id'];
                if (!$sameOffer) {
                    $this->walletMinutes()->assertAvailable($student, $values['offer_id'], $values['duration_minutes']);
                    $this->walletMinutes()->credit($student, $previous->offer_id, $previousMinutes);
                    $this->walletMinutes()->debit($student, $values['offer_id'], $values['duration_minutes']);
                } else {
                    $delta = $values['duration_minutes'] - $previousMinutes;
                    if ($delta > 0) {
                        $this->walletMinutes()->assertAvailable($student, $values['offer_id'], $delta);
                        $this->walletMinutes()->debit($student, $values['offer_id'], $delta);
                    } elseif ($delta < 0) {
                        $this->walletMinutes()->credit($student, $values['offer_id'], abs($delta));
                    }
                }

                Monitor::whereKey($monitor->id)->lockForUpdate()->firstOrFail();
                $this->checkConflicts($monitor, $student, $values, $reservation->id);
                $planning->update($reservation, $this->planningValues($values, $monitor));
            }

            $previous->update(['offer_id' => $values['offer_id'], 'session_type' => $values['type']]);
            $this->writeEvaluation($reservation, $student, $monitor, $values);

            return app(DrivingLessonMilestoneService::class)->forUpcomingLesson($student, $reservation);
        });

        return response()->json([
            'data' => $this->payload($reservation->fresh(['training.offer', 'evaluation', 'reviewMonitor'])),
            'milestone_alert' => app(DrivingLessonMilestoneService::class)->payload($milestoneAlert),
        ]);
    }

    public function destroy(Request $request, Student $student, Reservation $reservation, ReservationInterface $planning)
    {
        $monitor = $this->monitor($request);
        $this->authorizeLesson($monitor, $student, $reservation);

        DB::transaction(function () use ($student, $reservation, $planning) {
            $this->lockEditable($reservation);
            abort_if($this->hasEnded($reservation), 422, 'Une leçon passée ne peut pas être supprimée.');
            $training = $reservation->training;
            $this->walletMinutes()->credit($student, $training->offer_id, $this->durationMinutes($reservation));
            $training->delete();
            $reservation->unsetRelation('training');
            $planning->delete($reservation);
        });

        return response()->json(['success' => true]);
    }

    public function sign(Request $request, Student $student, Reservation $reservation)
    {
        $monitor = $this->monitor($request);
        $this->authorizeLesson($monitor, $student, $reservation);
        abort_unless((string) $request->user()?->monitor?->id === (string) $monitor->id, 403, 'Seul le moniteur de cette leçon peut la signer.');
        $signature = $request->validate([
            'accepted' => ['required', 'accepted'], 'strokes' => ['required', 'array', 'min:1', 'max:50'],
            'strokes.*' => ['required', 'array', 'min:2', 'max:500'],
            'strokes.*.*' => ['required', 'array:x,y'],
            'strokes.*.*.x' => ['required', 'numeric', 'min:0', 'max:600'],
            'strokes.*.*.y' => ['required', 'numeric', 'min:0', 'max:180'],
        ]);
        $length = 0;
        foreach ($signature['strokes'] as $stroke) {
            foreach (array_slice($stroke, 1) as $index => $point) {
                $length += hypot($point['x'] - $stroke[$index]['x'], $point['y'] - $stroke[$index]['y']);
            }
        }
        if ($length < 10) throw ValidationException::withMessages(['strokes' => 'Veuillez tracer une signature lisible.']);

        $review = DB::transaction(function () use ($reservation, $monitor, $student, $request, $signature) {
            $this->lockEditable($reservation);
            abort_unless($this->hasEnded($reservation), 422, 'La leçon doit être terminée avant la signature.');
            $data = $reservation->evaluation?->data ?? [];
            abort_if((bool) ($data['formation']['is_absent'] ?? $reservation->reviewMonitor?->is_absent ?? false), 422, 'Une leçon avec un élève absent ne peut pas être signée.');
            $data['formation_signature'] = [
                'strokes' => $signature['strokes'], 'signed_at' => now()->toIso8601String(),
                'signed_by' => (string) $request->user()->id, 'monitor_id' => $monitor->id,
                'digest' => hash('sha256', json_encode([$data, Rating::where('reservation_id', $reservation->id)->orderBy('id')->get()->toArray()])),
            ];
            DocumentEvaluation::updateOrCreate(['reservation_id' => $reservation->id, 'student_id' => $student->id], ['monitor_id' => $monitor->id, 'data' => $data]);
            return ReviewMonitor::updateOrCreate(['reservation_id' => $reservation->id], [
                'monitor_id' => $monitor->id, 'is_absent' => false,
                'comment' => ($data['public_observations'] ?? '') ?: 'Leçon terminée et signée par le moniteur.',
            ]);
        });
        try {
            app(SatisfactionOfferTriggerService::class)->processCompletedLesson($review);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json(['data' => $this->payload($reservation->fresh(['training.offer', 'evaluation', 'reviewMonitor']))]);
    }

    private function monitor(Request $request): Monitor
    {
        $user = $request->user();
        $monitor = $user?->monitor;
        if (!$monitor && $user?->hasAnyRole(['admin', 'super-admin']) && $request->filled('monitor_id')) {
            $monitor = Monitor::whereKey($request->input('monitor_id'))->orWhere('user_id', $request->input('monitor_id'))->first();
        }
        abort_unless($monitor, 403, 'Compte moniteur requis.');
        return $monitor;
    }

    private function department(Monitor $monitor): string
    {
        // The monitor profile stores the postal code on the user record. Older
        // accounts may only have the department in monitor_information.
        return trim((string) ($monitor->user?->postal
            ?: $monitor->user?->postal_code_1
            ?: $monitor->details?->departement
            ?: ''));
    }

    private function lessons(Monitor $monitor, Student $student)
    {
        return Reservation::where('monitor_id', $monitor->id)->whereHas('training', fn ($query) => $query->where('student_id', $student->id));
    }

    private function studentLessons(Student $student)
    {
        return Reservation::whereHas('training', fn ($query) => $query->where('student_id', $student->id));
    }

    private function bookletLessons(Student $student)
    {
        return $this->studentLessons($student)
            ->with(['training.offer', 'lieu.zone', 'monitor.user'])
            ->orderBy('date')->orderBy('start_at')->get();
    }

    private function authorizeStudent(Monitor $monitor, Student $student): void
    {
        abort_unless($this->lessons($monitor, $student)->exists(), 403, 'Ce candidat n’est pas lié à ce moniteur.');
    }

    private function authorizeLesson(Monitor $monitor, Student $student, Reservation $reservation): void
    {
        abort_unless($this->lessons($monitor, $student)->whereKey($reservation->id)->exists(), 403, 'Leçon non autorisée.');
    }

    private function lockEditable(Reservation $reservation): void
    {
        $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
        $reservation->setRawAttributes($locked->getAttributes(), true);
        $reservation->unsetRelation('evaluation');
        abort_if($this->isCancelled($reservation), 422, 'Une leçon annulée ne peut pas être modifiée.');
        abort_if(isset($reservation->evaluation?->data['formation_signature']), 423, 'Cette leçon est signée et ne peut plus être modifiée.');
    }

    private function schemaFromData(array $data): ?array
    {
        $schema = $data['formation']['schema'] ?? null;

        return is_array($schema) ? $schema : null;
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
    private function lockSchemaEditable(Reservation $reservation): void
    {
        $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
        $reservation->setRawAttributes($locked->getAttributes(), true);
        $reservation->unsetRelation('evaluation');
        abort_if($this->isCancelled($reservation), 422, 'Une leçon annulée ne peut pas recevoir de schéma.');
    }
    private function validated(Request $request, Monitor $monitor): array
    {
        $values = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'], 'start_at' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', Rule::in(self::DURATION_OPTIONS)], 'monitor_id' => ['required', Rule::in([$monitor->id])],
            'offer_id' => ['required', 'uuid', 'exists:offers,id'], 'lieu_id' => ['required', Rule::in($monitor->lieux()->pluck('lieux.id')->all())],
            'type' => ['required', 'string', 'max:100'], 'department' => ['nullable', Rule::in([$this->department($monitor), ''])],
            'boite_auto' => ['required', 'boolean'], 'evaluation_initiale' => ['required', 'boolean'], 'evaluation_permis' => ['required', 'boolean'],
            'is_absent' => ['required', 'boolean'],
            'public_observation' => ['nullable', 'string', 'max:10000'], 'teacher_observation' => ['nullable', 'string', 'max:10000'],
            'maneuvers' => ['present', 'array', 'max:100'], 'maneuvers.*' => ['required', 'string', 'max:300'],
            'maneuver_counts' => ['nullable', 'array'], 'maneuver_counts.*' => ['integer', 'min:0', 'max:9'],
        ]);

        foreach (array_keys($values['maneuver_counts'] ?? []) as $maneuverId) {
            if (!array_key_exists($maneuverId, self::MANEUVERS)) {
                throw ValidationException::withMessages(['maneuver_counts' => 'Une manœuvre sélectionnée est invalide.']);
            }
        }
        if ($values['evaluation_initiale'] && (int) $values['duration_minutes'] !== 60) {
            throw ValidationException::withMessages(['evaluation_initiale' => 'L’évaluation initiale nécessite une durée exacte de 1 heure.']);
        }

        $values['duration_minutes'] = (int) $values['duration_minutes'];
        [$hour, $minute] = array_map('intval', explode(':', $values['start_at']));
        $endMinutes = ($hour * 60) + $minute + $values['duration_minutes'];
        // The existing schedule accepts the entered lesson time. The duration is
        // validated independently; do not reject a valid lesson merely because
        // it crosses the old artificial 07:00–23:00 frontend boundary.
        $values['end_at'] = sprintf('%02d:%02d', intdiv($endMinutes % 1440, 60), $endMinutes % 60);

        return $values;
    }

    private function planningValues(array $values, Monitor $monitor): array
    {
        return [
            'monitor_id' => $monitor->id, 'date' => $values['date'], 'start_at' => $values['start_at'], 'end_at' => $values['end_at'],
            'hour' => (int) ceil($values['duration_minutes'] / 60), 'duration_minutes' => $values['duration_minutes'],
            'lieu_id' => $values['lieu_id'], 'is_active' => true,
        ];
    }

    private function checkConflicts(Monitor $monitor, Student $student, array $values, ?string $except = null): void
    {
        $conflict = Reservation::whereDate('date', $values['date'])
            ->whereTime('start_at', '<', $values['end_at'])->whereTime('end_at', '>', $values['start_at'])
            ->when($except, fn ($query) => $query->whereKeyNot($except))->whereHas('training')
            ->where(fn ($query) => $query->where('monitor_id', $monitor->id)->orWhereHas('training', fn ($training) => $training->where('student_id', $student->id)))->exists();
        abort_if($conflict, 422, 'Le moniteur ou l’élève a déjà une leçon sur ce créneau.');
    }

    private function writeEvaluation(Reservation $reservation, Student $student, Monitor $monitor, array $values): void
    {
        $data = $reservation->evaluation?->data ?? [];
        $counts = array_key_exists('maneuver_counts', $values)
            ? $this->normalizeManeuverCounts($values['maneuver_counts'] ?? [])
            : $this->maneuverCountsFromData($data);
        $legacyManeuvers = $this->legacyManeuvers($values['maneuvers'] ?? []);
        $data['formation'] = array_merge($data['formation'] ?? [], [
            'department' => $values['department'] ?? '',
            'boite_auto' => $values['boite_auto'],
            'is_absent' => $values['is_absent'],
            'teacher_observation' => $values['teacher_observation'] ?? '',
            'maneuver_counts' => $counts,
        ]);
        $data['evaluation_initiale'] = $values['evaluation_initiale'];
        $data['evaluation_permis'] = $values['evaluation_permis'];
        $data['public_observations'] = $values['public_observation'] ?? '';
        $data['manoeuvres'] = array_values(array_merge($legacyManeuvers, $this->maneuverLabels($counts)));
        DocumentEvaluation::updateOrCreate(['student_id' => $student->id, 'reservation_id' => $reservation->id], ['monitor_id' => $monitor->id, 'data' => $data]);
    }

    private function initialEvaluationReservation(Monitor $monitor, Student $student): Reservation
    {
        $lessons = $this->lessons($monitor, $student)
            ->with(['evaluation', 'training'])
            ->orderBy('date')
            ->orderBy('start_at')
            ->get();

        $reservation = $lessons->first(function (Reservation $lesson) {
            $data = $lesson->evaluation?->data ?? [];

            return is_array($data) && (
                isset($data['student']) ||
                isset($data['training']) ||
                isset($data['experience']) ||
                isset($data['results'])
            );
        }) ?? $lessons->first(function (Reservation $lesson) {
            return (bool) (($lesson->evaluation?->data ?? [])['evaluation_initiale'] ?? false);
        }) ?? $lessons->first();

        abort_unless($reservation, 404, 'Aucune leçon n’est disponible pour cette évaluation.');

        return $reservation;
    }

    private function initialEvaluationPayload(Student $student, Reservation $reservation): array
    {
        // The fiche is a monitor view of the student dossier. Load every linked
        // record here so the client never has to invent profile, lesson, offer,
        // instructor, or location values.
        $student->loadMissing('user');
        $reservation->loadMissing(['evaluation', 'training.offer', 'lieu', 'monitor.user']);

        $evaluation = $reservation->evaluation;
        $training = $reservation->training;
        $data = $this->initialEvaluationData($evaluation?->data ?? [], $student);
        $locked = $this->isCancelled($reservation) || isset(($evaluation?->data ?? [])['formation_signature']);
        $bookedLessons = $student->getBookedTrainingCount();
        $bookedHours = $student->getBookedTrainingHours();

        return [
            'evaluation' => [
                'id' => $evaluation?->id,
                'reservation_id' => $reservation->id,
                'data' => $data,
                'created_at' => $evaluation?->created_at?->toIso8601String(),
                'updated_at' => $evaluation?->updated_at?->toIso8601String(),
            ],
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name ?? 'Élève',
                'email' => $student->user?->email,
                'date_of_birth' => $student->user?->date_naissance,
                'phone' => $student->user?->phone,
                'address' => $student->user?->adresse,
                'postal_code' => $student->user?->postal ?: $student->user?->postal_code_1,
                'city' => $student->user?->ville,
                'neph' => $student->neph,
                'neph_status' => $student->neph_status,
                'box_type' => (string) $student->boite_type === '1' ? 'Boîte automatique' : 'Boîte mécanique',
                'media' => $student->user?->media,
                'profile_photo_url' => $student->user?->profile_photo_url,
                'avatar' => $student->user?->avatar,
                'photo' => $student->user?->photo,
            ],
            'formation' => [
                'reservation_id' => $reservation->id,
                'date' => $reservation->date?->toDateString(),
                'start_at' => $reservation->start_at?->format('H:i'),
                'end_at' => $reservation->end_at?->format('H:i'),
                'duration_minutes' => $this->durationMinutes($reservation),
                'title' => $training?->prestation ?: $training?->session_type ?: 'Leçon de conduite',
                'session_type' => $training?->session_type,
                'offer_name' => $training?->offer?->name,
                'location_name' => $reservation->lieu?->name,
                'monitor_name' => $reservation->monitor?->user?->name,
                'booked_lessons' => $bookedLessons,
                'booked_hours' => $bookedHours,
            ],
            'editable' => ! $locked,
            'locked_reason' => $this->isCancelled($reservation)
                ? 'Cette fiche est verrouillée car la leçon associée est annulée.'
                : (isset(($evaluation?->data ?? [])['formation_signature']) ? 'Cette fiche est verrouillée car la leçon associée est signée.' : null),
            'contract' => [
                'available' => $student->canViewContract(),
                'created_at' => $evaluation?->created_at?->toIso8601String(),
                'proposal_accepted' => $data['results']['proposal_accepted'],
            ],
            // There is no existing initial-evaluation mail workflow in the
            // application, so the UI deliberately does not claim an e-mail
            // was sent.
            'email' => [
                'available' => false,
                'reason' => 'L’envoi par e-mail de la fiche d’évaluation n’est pas encore configuré.',
            ],
        ];
    }
    private function initialEvaluationQuestionsPayload(Student $student, Reservation $reservation): array
    {
        $base = $this->initialEvaluationPayload($student, $reservation);
        $stored = $reservation->evaluation?->data['initial_evaluation_questions'] ?? [];
        $answers = is_array($stored['answers'] ?? null) ? $stored['answers'] : [];
        $final = is_array($stored['final'] ?? null) ? $stored['final'] : [];
        $result = $base['evaluation']['data']['results'];

        return [
            'student' => $base['student'],
            'evaluation' => [
                'id' => $base['evaluation']['id'],
                'reservation_id' => $base['evaluation']['reservation_id'],
                'answers' => $answers,
                'current_question' => max(1, min(51, (int) ($stored['current_question'] ?? 1))),
                'status' => in_array($stored['status'] ?? '', ['in_progress', 'validated'], true) ? $stored['status'] : 'in_progress',
                'final' => [
                    'score' => array_key_exists('score', $final) ? $final['score'] : $result['score'],
                    'lessons_proposed' => array_key_exists('lessons_proposed', $final) ? $final['lessons_proposed'] : $result['lessons_proposed'],
                    'guidance' => (string) ($final['guidance'] ?? ''),
                ],
                'updated_at' => $stored['updated_at'] ?? $base['evaluation']['updated_at'],
            ],
            'editable' => $base['editable'],
            'locked_reason' => $base['locked_reason'],
        ];
    }

    private function validatedInitialEvaluationQuestions(Request $request): array
    {
        $values = $request->validate([
            'answers' => ['nullable', 'array', 'max:51'],
            'answers.*' => ['nullable'],
            'current_question' => ['nullable', 'integer', 'between:1,51'],
            'validated' => ['nullable', 'boolean'],
            'final' => ['nullable', 'array'],
            'final.score' => ['nullable', 'numeric', 'between:0,100'],
            'final.lessons_proposed' => ['nullable', 'integer', 'between:0,999'],
            'final.guidance' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach (array_keys($values['answers'] ?? []) as $key) {
            if (!preg_match('/^q(?:0[1-9]|[1-4][0-9]|5[0-1])$/', (string) $key)) {
                throw ValidationException::withMessages(['answers' => 'Une réponse de questionnaire est invalide.']);
            }
        }

        return $values;
    }

    private function mapInitialEvaluationQuestionAnswers(array &$data, array $answers, array $final): void
    {
        $has = fn (string $key, string $value): bool => in_array($value, (array) ($answers[$key] ?? []), true);
        $yes = fn (string $key): bool => ($answers[$key] ?? null) === 'Oui';

        if (is_string($answers['q03'] ?? null)) $data['student']['profession'] = $answers['q03'];
        if (array_key_exists('q05', $answers)) $data['student']['wears_correction'] = $yes('q05');
        if (is_string($answers['q06'] ?? null)) $data['student']['visual_acuity'] = $answers['q06'];
        if (is_string($answers['q07'] ?? null)) $data['student']['ophthalmologist_appointment'] = $answers['q07'];

        if (array_key_exists('q01', $answers)) {
            $data['training'] = array_merge($data['training'] ?? [], [
                'automatic' => $has('q01', 'Boîte automatique'),
                'manual' => $has('q01', 'Boîte mécanique'),
                'adapted_vehicle' => $has('q01', 'Véhicule aménagé'),
                'accompanied_driving' => $has('q01', 'Conduite accompagnée'),
                'supervised_driving' => $has('q01', 'Conduite supervisée'),
            ]);
        }
        if (array_key_exists('q10', $answers)) $data['experience']['license'] = array_merge($data['experience']['license'] ?? [], ['am' => $yes('q10')]);
        if (array_key_exists('q08', $answers)) $data['experience']['driving'] = array_merge($data['experience']['driving'] ?? [], ['already_driven' => $yes('q08')]);
        $vehicleMappings = ['q09' => 'moped', 'q11' => 'bike', 'q12' => 'tractor', 'q13' => 'cart', 'q14' => 'quad', 'q15' => 'lawnmower', 'q16' => 'trolley'];
        foreach ($vehicleMappings as $question => $field) {
            if (array_key_exists($question, $answers)) {
                $data['experience']['other_vehicles'] = array_merge($data['experience']['other_vehicles'] ?? [], [$field => $yes($question)]);
            }
        }

        if (isset($answers['q17'])) {
            $data['attitude']['master_car_and_code'] = $answers['q17'] === 'Maîtriser la voiture et le code';
            $data['attitude']['anticipate_difficulties'] = $answers['q17'] === 'Prévoir les difficultés, savoir y faire face';
        }
        if (isset($answers['q18'])) {
            $data['attitude']['inevitable_step'] = $answers['q18'] === 'Un passage inévitable';
            $data['attitude']['desire_to_do_it'] = $answers['q18'] === 'Une envie de connaître';
        }
        if (array_key_exists('score', $final)) $data['results']['score'] = $final['score'];
        if (array_key_exists('lessons_proposed', $final)) $data['results']['lessons_proposed'] = $final['lessons_proposed'];
    }
    private function initialEvaluationData(array $stored, Student $student): array
    {
        $bool = fn ($section, $key, $fallback = false): bool => (bool) (($stored[$section][$key] ?? $fallback));
        $score = fn ($section, $key): int => max(0, min(10, (int) ($stored[$section][$key] ?? 0)));

        return [
            'student' => [
                'profession' => (string) ($stored['student']['profession'] ?? ''),
                'wears_correction' => $bool('student', 'wears_correction'),
                'visual_acuity' => (string) ($stored['student']['visual_acuity'] ?? ''),
            ],
            'training' => [
                'automatic' => $bool('training', 'automatic', (string) $student->boite_type === '1'),
                'manual' => $bool('training', 'manual', (string) $student->boite_type !== '1'),
                'adapted_vehicle' => $bool('training', 'adapted_vehicle'),
                'accompanied_driving' => $bool('training', 'accompanied_driving'),
                'supervised_driving' => $bool('training', 'supervised_driving'),
            ],
            'experience' => [
                'license' => [
                    'am' => (bool) ($stored['experience']['license']['am'] ?? false),
                    'b1' => (bool) ($stored['experience']['license']['b1'] ?? false),
                    'a1' => (bool) ($stored['experience']['license']['a1'] ?? false),
                ],
                'driving' => [
                    'already_driven' => (bool) ($stored['experience']['driving']['already_driven'] ?? false),
                    'with_parents' => (bool) ($stored['experience']['driving']['with_parents'] ?? false),
                    'with_driving_school' => (bool) ($stored['experience']['driving']['with_driving_school'] ?? false),
                ],
                'other_vehicles' => [
                    'moped' => (bool) ($stored['experience']['other_vehicles']['moped'] ?? false),
                    'bike' => (bool) ($stored['experience']['other_vehicles']['bike'] ?? false),
                    'tractor' => (bool) ($stored['experience']['other_vehicles']['tractor'] ?? false),
                    'cart' => (bool) ($stored['experience']['other_vehicles']['cart'] ?? false),
                    'quad' => (bool) ($stored['experience']['other_vehicles']['quad'] ?? false),
                    'lawnmower' => (bool) ($stored['experience']['other_vehicles']['lawnmower'] ?? false),
                    'trolley' => (bool) ($stored['experience']['other_vehicles']['trolley'] ?? false),
                ],
            ],
            'vehicle_knowledge' => [
                'steering' => $bool('vehicle_knowledge', 'steering'),
                'clutch' => $bool('vehicle_knowledge', 'clutch'),
                'gearbox' => $bool('vehicle_knowledge', 'gearbox'),
                'braking' => $bool('vehicle_knowledge', 'braking'),
            ],
            'attitude' => [
                'master_car_and_code' => $bool('attitude', 'master_car_and_code'),
                'anticipate_difficulties' => $bool('attitude', 'anticipate_difficulties'),
                'inevitable_step' => $bool('attitude', 'inevitable_step'),
                'desire_to_do_it' => $bool('attitude', 'desire_to_do_it'),
            ],
            'skills' => [
                'vehicle_setup' => $score('skills', 'vehicle_setup'),
                'starting' => $score('skills', 'starting'),
                'stopping' => $score('skills', 'stopping'),
                'steering_wheel' => $score('skills', 'steering_wheel'),
            ],
            'understanding' => [
                'comprehension' => $score('understanding', 'comprehension'),
                'restitution' => $score('understanding', 'restitution'),
            ],
            'environment' => [
                'trajectory' => $score('environment', 'trajectory'),
                'orientation' => $score('environment', 'orientation'),
                'observation' => $score('environment', 'observation'),
                'look' => $score('environment', 'look'),
            ],
            'emotions' => [
                'relationship' => $score('emotions', 'relationship'),
                'tension' => $score('emotions', 'tension'),
            ],
            'results' => [
                'score' => isset($stored['results']['score']) && $stored['results']['score'] !== '' ? (float) $stored['results']['score'] : null,
                'lessons_proposed' => isset($stored['results']['lessons_proposed']) && $stored['results']['lessons_proposed'] !== '' ? (int) $stored['results']['lessons_proposed'] : null,
                'proposal_accepted' => ($stored['results']['proposal_accepted'] ?? 'Non') === 'Oui' ? 'Oui' : 'Non',
                'parent_signature' => (string) ($stored['results']['parent_signature'] ?? ''),
                'student_signature' => (string) ($stored['results']['student_signature'] ?? ''),
                'monitor_signature' => (string) ($stored['results']['monitor_signature'] ?? ''),
            ],
        ];
    }

    private function validatedInitialEvaluation(Request $request): array
    {
        return $request->validate([
            'data' => ['required', 'array'],
            'data.student' => ['required', 'array'],
            'data.student.profession' => ['nullable', 'string', 'max:200'],
            'data.student.wears_correction' => ['required', 'boolean'],
            'data.student.visual_acuity' => ['nullable', 'string', 'max:120'],
            'data.training' => ['required', 'array'],
            'data.training.automatic' => ['required', 'boolean'],
            'data.training.manual' => ['required', 'boolean'],
            'data.training.adapted_vehicle' => ['required', 'boolean'],
            'data.training.accompanied_driving' => ['required', 'boolean'],
            'data.training.supervised_driving' => ['required', 'boolean'],
            'data.experience' => ['required', 'array'],
            'data.experience.license.am' => ['required', 'boolean'],
            'data.experience.license.b1' => ['required', 'boolean'],
            'data.experience.license.a1' => ['required', 'boolean'],
            'data.experience.driving.already_driven' => ['required', 'boolean'],
            'data.experience.driving.with_parents' => ['required', 'boolean'],
            'data.experience.driving.with_driving_school' => ['required', 'boolean'],
            'data.experience.other_vehicles.moped' => ['required', 'boolean'],
            'data.experience.other_vehicles.bike' => ['required', 'boolean'],
            'data.experience.other_vehicles.tractor' => ['required', 'boolean'],
            'data.experience.other_vehicles.cart' => ['required', 'boolean'],
            'data.experience.other_vehicles.quad' => ['required', 'boolean'],
            'data.experience.other_vehicles.lawnmower' => ['required', 'boolean'],
            'data.experience.other_vehicles.trolley' => ['required', 'boolean'],
            'data.vehicle_knowledge' => ['required', 'array'],
            'data.vehicle_knowledge.steering' => ['required', 'boolean'],
            'data.vehicle_knowledge.clutch' => ['required', 'boolean'],
            'data.vehicle_knowledge.gearbox' => ['required', 'boolean'],
            'data.vehicle_knowledge.braking' => ['required', 'boolean'],
            'data.attitude' => ['required', 'array'],
            'data.attitude.master_car_and_code' => ['required', 'boolean'],
            'data.attitude.anticipate_difficulties' => ['required', 'boolean'],
            'data.attitude.inevitable_step' => ['required', 'boolean'],
            'data.attitude.desire_to_do_it' => ['required', 'boolean'],
            'data.skills' => ['required', 'array'],
            'data.skills.vehicle_setup' => ['required', 'integer', 'between:0,10'],
            'data.skills.starting' => ['required', 'integer', 'between:0,10'],
            'data.skills.stopping' => ['required', 'integer', 'between:0,10'],
            'data.skills.steering_wheel' => ['required', 'integer', 'between:0,10'],
            'data.understanding.comprehension' => ['required', 'integer', 'between:0,10'],
            'data.understanding.restitution' => ['required', 'integer', 'between:0,10'],
            'data.environment.trajectory' => ['required', 'integer', 'between:0,10'],
            'data.environment.orientation' => ['required', 'integer', 'between:0,10'],
            'data.environment.observation' => ['required', 'integer', 'between:0,10'],
            'data.environment.look' => ['required', 'integer', 'between:0,10'],
            'data.emotions.relationship' => ['required', 'integer', 'between:0,10'],
            'data.emotions.tension' => ['required', 'integer', 'between:0,10'],
            'data.results' => ['required', 'array'],
            'data.results.score' => ['nullable', 'numeric', 'between:0,100'],
            'data.results.lessons_proposed' => ['nullable', 'integer', 'between:0,999'],
            'data.results.proposal_accepted' => ['required', Rule::in(['Oui', 'Non'])],
            'data.results.parent_signature' => ['nullable', 'string', 'max:500'],
            'data.results.student_signature' => ['nullable', 'string', 'max:500'],
            'data.results.monitor_signature' => ['nullable', 'string', 'max:1000000'],
        ]);
    }
    private function normalizeManeuverCounts(array $counts): array
    {
        return collect($counts)->mapWithKeys(fn ($count, $id) => [(string) $id => max(0, min(9, (int) $count))])
            ->filter(fn ($count, $id) => $count > 0 && array_key_exists($id, self::MANEUVERS))->all();
    }

    private function maneuverCountsFromData(array $data): array
    {
        $stored = $data['formation']['maneuver_counts'] ?? null;
        if (is_array($stored)) return $this->normalizeManeuverCounts($stored);
        $counts = [];
        foreach ((array) ($data['manoeuvres'] ?? $data['maneuvers'] ?? []) as $maneuver) {
            $id = array_search($maneuver, self::MANEUVERS, true);
            if ($id !== false) $counts[$id] = ($counts[$id] ?? 0) + 1;
        }
        return $this->normalizeManeuverCounts($counts);
    }

    private function maneuverLabels(array $counts): array
    {
        return collect($counts)->map(fn ($count, $id) => $count === 1 ? self::MANEUVERS[$id] : self::MANEUVERS[$id].' × '.$count)->values()->all();
    }

    private function legacyManeuvers(array $maneuvers): array
    {
        return collect($maneuvers)->filter(function ($maneuver) {
            if (!is_string($maneuver)) return false;
            foreach (self::MANEUVERS as $label) {
                if ($maneuver === $label || preg_match('/^'.preg_quote($label, '/').' × [1-9]$/u', $maneuver)) return false;
            }
            return true;
        })->values()->all();
    }

    private function durationMinutes(Reservation $reservation): int
    {
        return $reservation->duration_minutes === null ? (int) $reservation->hour * 60 : (int) $reservation->duration_minutes;
    }

    private function isCancelled(Reservation $reservation): bool
    {
        return !$reservation->is_active || (int) $reservation->training?->cancellation?->status === 3;
    }

    private function hasEnded(Reservation $reservation): bool
    {
        return Carbon::parse($reservation->date->toDateString().' '.$reservation->end_at->format('H:i'))->isPast();
    }

    private function walletMinutes(): FormationWalletMinutesService
    {
        return app(FormationWalletMinutesService::class);
    }

    private function payload(Reservation $reservation, ?int $number = null, ?\Illuminate\Support\Collection $lessonRatings = null, ?Monitor $viewer = null): array
    {
        $data = $reservation->evaluation?->data ?? [];
        $meta = $data['formation'] ?? [];
        $absent = (bool) ($meta['is_absent'] ?? $reservation->reviewMonitor?->is_absent ?? false);
        $signed = $data['formation_signature'] ?? null;
        $ended = $this->hasEnded($reservation);
        $cancelled = $this->isCancelled($reservation);
        $duration = $this->durationMinutes($reservation);
        $ratings = $lessonRatings ?? Rating::where('reservation_id', $reservation->id)->with(['competency', 'evaluationDetail'])->get();
        $maneuverCounts = $this->maneuverCountsFromData($data);

        return [
            'id' => $reservation->id, 'number' => $number, 'date' => $reservation->date->toDateString(),
            'duration_minutes' => $duration, 'hour' => $duration / 60, 'start_at' => $reservation->start_at->format('H:i'), 'monitor_id' => $reservation->monitor_id,
            'offer_id' => $reservation->training?->offer_id, 'offer_name' => $reservation->training?->offer?->name,
            'lieu_id' => $reservation->lieu_id, 'type' => $reservation->training?->session_type ?: 'Prestation',
            'department' => ($meta['department'] ?? '') ?: $this->department($reservation->monitor),
            'boite_auto' => (bool) ($meta['boite_auto'] ?? $reservation->training?->offer?->is_auto ?? false),
            'evaluation_initiale' => $duration === 60 && (bool) ($data['evaluation_initiale'] ?? false),
            'evaluation_permis' => (bool) ($data['evaluation_permis'] ?? false),
            'public_observation' => $data['public_observations'] ?? '',
            'teacher_observation' => $meta['teacher_observation'] ?? $reservation->reviewMonitor?->comment ?? '',
            'maneuvers' => array_values((array) ($data['manoeuvres'] ?? $data['maneuvers'] ?? [])), 'maneuver_counts' => $maneuverCounts, 'has_schema' => is_array($meta['schema'] ?? null), 'is_absent' => $absent,
            'status' => $cancelled ? 'cancelled' : ($signed ? 'signed' : ($absent ? 'absent' : ($ended && $reservation->reviewMonitor?->comment ? 'completed' : ($ended ? 'past' : 'upcoming')))),
            'signature' => $signed, 'can_manage' => !$viewer || (string) $reservation->monitor_id === (string) $viewer->id, 'can_delete' => !$signed && !$ended && !$cancelled,
            'can_reschedule' => !$signed && !$ended && !$cancelled,
            'competencies' => $ratings->map(fn ($rating) => ['id' => $rating->competency_id, 'title' => $rating->competency?->label, 'rating' => (int) $rating->rating, 'observations' => $rating->comment ?? '', 'studentEvaluation' => (bool) ($rating->evaluationDetail?->student_evaluation ?? false), 'studentResponse' => $rating->evaluationDetail?->student_response ?? '', 'activeTab' => $rating->evaluationDetail?->active_tab ?? 'pourquoi'])->values(),
        ];
    }
}
