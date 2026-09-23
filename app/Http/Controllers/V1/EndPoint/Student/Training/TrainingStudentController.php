<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Training;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Training\Job\Dashbord\FetchCoursRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin\FetchByWeekAllAvailableReservationRepo;
use App\Services\Student\Training\info\TrainingInterface;

//use App\Repository\Admin\User\GetAllAdmins;

class TrainingStudentController extends Controller
{
    public function __construct(private TrainingInterface $trainingService) {}


    /**
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if (!$student) {
            return response()->json(['message' => 'Profil élève introuvable.', 'data' => []], 422);
        }

        return response()->json(FetchCoursRepo::run([
            ...$request->all(),
            'student_id' => $student->id,
        ]));
    }

    /**
     * @return JsonResponse
     */
    public function get()
    {
        return response()->json([
            "data" => FetchByWeekAllAvailableReservationRepo::run(request()->all())
        ]);
    }

    /** Courses belonging to the authenticated student, for one calendar month. */
    public function courses(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if (!$student) {
            return response()->json(['message' => 'Profil élève introuvable.', 'courses' => [], 'activity_dates' => []], 422);
        }

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = Carbon::createFromFormat('Y-m', $validated['month'] ?? now()->format('Y-m'))->startOfMonth();
        $selectedDate = $validated['date'] ?? now()->toDateString();

        $trainings = Training::withTrashed()
            ->with([
                'cancellation',
                'offer:id,name,color',
                'reservation' => fn ($query) => $query->withTrashed()->with([
                    'monitor.user:id,name,first_name,last_name',
                    'lieu.zone:id,name',
                ]),
            ])
            ->where('student_id', $student->id)
            ->get()
            ->filter(fn (Training $training) => $training->reservation)
            ->values();

        $activityDates = $trainings
            ->filter(fn (Training $training) => $training->reservation->date?->betweenIncluded($month, $month->copy()->endOfMonth()))
            ->map(fn (Training $training) => $training->reservation->date->toDateString())
            ->unique()
            ->values();

        return response()->json([
            'courses' => $trainings
                ->filter(fn (Training $training) => $training->reservation->date?->toDateString() === $selectedDate)
                ->sortBy('reservation.start_at')
                ->map(fn (Training $training) => $this->coursePayload($training))
                ->values(),
            'activity_dates' => $activityDates,
        ]);
    }

    private function coursePayload(Training $training): array
    {
        $reservation = $training->reservation;
        $monitor = $reservation->monitor?->user;
        $monitorName = $monitor?->name ?: trim(implode(' ', array_filter([$monitor?->first_name, $monitor?->last_name])));
        $isCancelled = $training->trashed()
            || $reservation->trashed()
            || (int) ($training->cancellation?->status ?? 0) === 3;
        $isPast = $reservation->date->isPast()
            || ($reservation->date->isToday() && $reservation->end_at?->format('H:i') < now()->format('H:i'));

        return [
            'id' => $training->id,
            'reservation_id' => $reservation->id,
            'date' => $reservation->date->toDateString(),
            'start_at' => $reservation->start_at?->format('H:i'),
            'end_at' => $reservation->end_at?->format('H:i'),
            'hour' => (float) $reservation->hour,
            'title' => $training->prestation ?: $training->session_type ?: 'Leçon de conduite',
            'type' => $training->session_type ?: 'Leçon de conduite',
            'offer_name' => $training->offer?->name,
            'offer_color' => $training->offer?->color,
            'location' => $reservation->lieu?->name
                ?: ($reservation->lieu?->zone?->name ?: 'Lieu à confirmer'),
            'location_url' => $reservation->lieu?->url,
            'instructor' => $monitorName ?: 'Moniteur à confirmer',
            'status' => $isCancelled ? 'cancelled' : ($isPast ? 'past' : 'upcoming'),
        ];
    }

    /** Reserve one free monitor availability for the authenticated student. */
    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_id' => ['required', 'uuid'],
            'offer_id' => ['nullable', 'uuid'],
            'student_id' => ['nullable', 'uuid'],
        ]);

        $user = auth()->user();
        $student = $user?->student
            ?? Student::query()->where('user_id', $user?->id)->first();

        // Admin and secretary dashboards may book a session while viewing a
        // particular student's dashboard. A student account always stays on
        // its own profile, regardless of a supplied student_id.
        if (!$student && !empty($validated['student_id']) && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $student = Student::query()->find($validated['student_id']);
        }

        if (!$student) {
            return response()->json(['message' => 'Profil élève introuvable.'], 422);
        }

        try {
            $training = DB::transaction(function () use ($validated, $student) {
                $reservation = Reservation::query()
                    ->whereKey($validated['reservation_id'])
                    ->where('is_active', true)
                    ->whereDate('date', '>=', now()->toDateString())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($reservation->training()->exists()) {
                    abort(422, 'Ce créneau vient déjà d’être réservé.');
                }

                $offerId = $validated['offer_id']
                    ?? $student->wallets()->whereNotNull('offer_id')->value('offer_id');

                if (!$offerId) {
                    abort(422, 'Aucune offre active n’est disponible pour réserver une séance.');
                }

                $wallet = $student->wallets()
                    ->where('offer_id', $offerId)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    abort(422, "L'offre sélectionnée n'appartient pas à votre compte élève.");
                }

                return $this->trainingService->create([
                    'student_id' => $student->id,
                    'offer_id' => $offerId,
                ], $reservation);
            });

            return response()->json([
                'message' => 'Séance réservée avec succès.',
                'data' => $training->load('reservation.monitor.user', 'reservation.lieu', 'offer'),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Créneau indisponible.'], 422);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getStatusCode());
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage() ?: 'Impossible de réserver ce créneau.'], 422);
        }
    }

    private function resolveStudent(Request $request): ?Student
    {
        $user = auth()->user();
        $student = $user?->student
            ?? Student::query()->where('user_id', $user?->id)->first();
        $requestedStudentId = $request->input('student_id');

        if (!$student && $requestedStudentId && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $student = Student::query()->find($requestedStudentId);
        }

        return $student;
    }
}
