<?php

namespace App\Services;

use App\Models\DrivingLessonMilestoneAlert;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DrivingLessonMilestoneService
{
    private const AUTO_THRESHOLD_MINUTES = 9 * 60;
    private const MANUAL_THRESHOLD_MINUTES = 14 * 60;
    private ?bool $hasDurationMinutesColumn = null;

    /**
     * Create (or return) the pending alert for a newly scheduled driving
     * lesson. A completed lesson is an active, passed reservation assigned to
     * this student. Soft-deleted training/reservation records are excluded by
     * the existing model scopes.
     */
    public function forUpcomingLesson(Student $student, Reservation $reservation): ?DrivingLessonMilestoneAlert
    {
        if (! $reservation->is_active || ! $reservation->training()->exists()) {
            return null;
        }

        return $this->pendingForStudent($student, $reservation);
    }

    /**
     * Return one student's single outstanding milestone notice. This is used
     * while scheduling a lesson and by the staff dashboard scanner.
     */
    public function pendingForStudent(Student $student, ?Reservation $reservation = null): ?DrivingLessonMilestoneAlert
    {
        $definition = $this->definition($student);
        $completedMinutes = $this->completedMinutes($student);

        if ($completedMinutes < $definition['threshold_minutes']) {
            return null;
        }

        return $this->createPendingAlert($student, $definition, $completedMinutes, $reservation);
    }

    private function createPendingAlert(
        Student $student,
        array $definition,
        int $completedMinutes,
        ?Reservation $reservation = null,
    ): ?DrivingLessonMilestoneAlert {
        $attributes = [
            'student_id' => $student->id,
            'milestone_hour' => $definition['milestone_hour'],
        ];

        try {
            $alert = DrivingLessonMilestoneAlert::query()->firstOrCreate($attributes, [
                'completed_minutes' => $completedMinutes,
                'reservation_id' => $reservation?->id,
            ]);
        } catch (QueryException) {
            // The unique index is the source of truth when two staff members
            // schedule simultaneously for the same student.
            $alert = DrivingLessonMilestoneAlert::query()->where($attributes)->firstOrFail();
        }

        return $alert->acknowledged_at ? null : $alert;
    }

    /**
     * Sum real elapsed lesson minutes. New reservations have an explicit
     * duration_minutes value; legacy rows use their start/end times first and
     * only fall back to the historical integer-hour field when needed.
     */
    public function completedMinutes(Student $student): int
    {
        $reservations = Reservation::query()
            ->select(['id', 'start_at', 'end_at', 'hour'])
            ->where('is_active', true)
            ->isPassed()
            ->whereDoesntHave('reviewMonitor', fn ($review) => $review->where('is_absent', true))
            ->whereHas('training', fn ($training) => $training->where('student_id', $student->id));

        // Older installations calculate duration from start/end and do not
        // have this newer optional column. Only select it when it exists so
        // the alert endpoint works with the live database schema.
        if ($this->hasDurationMinutesColumn()) {
            $reservations->addSelect('duration_minutes');
        }

        return (int) $reservations->get()
            ->sum(fn (Reservation $reservation) => $this->durationMinutes($reservation));
    }

    /**
     * Return one pending alert for the admin/secretary dashboard. If a
     * completed student does not yet have a milestone row, create one here.
     * The dashboard receives one alert at a time, never a flood of popups.
     */
    public function nextPendingForStaff(?string $zoneId = null, bool $includeUnassignedZone = false): ?DrivingLessonMilestoneAlert
    {
        $pending = DrivingLessonMilestoneAlert::query()
            ->whereNull('acknowledged_at')
            ->with('student.user')
            ->when($zoneId, function ($query) use ($zoneId, $includeUnassignedZone) {
                $query->whereHas('student.user', function ($user) use ($zoneId, $includeUnassignedZone) {
                    $user->where('zone_id', $zoneId);
                    if ($includeUnassignedZone) {
                        $user->orWhereNull('zone_id');
                    }
                });
            })
            ->oldest('created_at')
            ->first();

        if ($pending) {
            return $pending;
        }

        $students = Student::query()
            ->select(['id', 'user_id', 'boite_type'])
            ->with('user:id,name,first_name,last_name,zone_id')
            ->when($zoneId, function ($query) use ($zoneId, $includeUnassignedZone) {
                $query->whereHas('user', function ($user) use ($zoneId, $includeUnassignedZone) {
                    $user->where('zone_id', $zoneId);
                    if ($includeUnassignedZone) {
                        $user->orWhereNull('zone_id');
                    }
                });
            })
            ->orderBy('created_at')
            ->get();

        if ($students->isEmpty()) {
            return null;
        }

        $existing = DrivingLessonMilestoneAlert::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->get(['student_id', 'milestone_hour'])
            ->mapWithKeys(fn (DrivingLessonMilestoneAlert $alert) => [
                $alert->student_id.'-'.$alert->milestone_hour => true,
            ]);

        $completedMinutesByStudent = $this->completedMinutesByStudentIds($students->pluck('id')->all());

        foreach ($students as $student) {
            $definition = $this->definition($student);
            $completedMinutes = $completedMinutesByStudent[(string) $student->id] ?? 0;
            $key = $student->id.'-'.$definition['milestone_hour'];

            // An acknowledged milestone remains in MySQL so the same student
            // can never generate this popup twice.
            if ($completedMinutes < $definition['threshold_minutes'] || $existing->has($key)) {
                continue;
            }

            $alert = $this->createPendingAlert($student, $definition, $completedMinutes);
            if ($alert) {
                return $alert->loadMissing('student.user');
            }
        }

        return null;
    }

    public function payload(?DrivingLessonMilestoneAlert $alert): ?array
    {
        if (! $alert) {
            return null;
        }

        $student = $alert->student ?: Student::query()->findOrFail($alert->student_id);
        $student->loadMissing('user:id,name,first_name,last_name');
        $definition = $this->definition($student);
        $studentName = $student->user?->name
            ?: trim(implode(' ', array_filter([$student->user?->first_name, $student->user?->last_name])));

        return [
            'id' => $alert->id,
            'student_id' => $alert->student_id,
            'reservation_id' => $alert->reservation_id,
            'milestone_hour' => $alert->milestone_hour,
            'completed_minutes' => $alert->completed_minutes,
            'student_name' => $studentName ?: 'Élève',
            'message' => $definition['message'],
        ];
    }

    private function definition(Student $student): array
    {
        $rawType = mb_strtolower(trim((string) $student->boite_type));
        $automatic = $rawType === '1' || str_contains($rawType, 'auto');

        return $automatic
            ? [
                'threshold_minutes' => self::AUTO_THRESHOLD_MINUTES,
                'milestone_hour' => 10,
                'message' => "Attention 10e heure de conduite pour l'élève. Faire un bilan.",
            ]
            : [
                'threshold_minutes' => self::MANUAL_THRESHOLD_MINUTES,
                'milestone_hour' => 15,
                'message' => "Attention 15eme heure de conduite pour l'élève. Faire un bilan.",
            ];
    }

    private function durationMinutes(?Reservation $reservation): int
    {
        if (! $reservation) {
            return 0;
        }

        if ($reservation->start_at && $reservation->end_at) {
            $start = Carbon::parse('2000-01-01 '.$reservation->start_at->format('H:i'));
            $end = Carbon::parse('2000-01-01 '.$reservation->end_at->format('H:i'));
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $minutes = $start->diffInMinutes($end);
            if ($minutes > 0) {
                return $minutes;
            }
        }

        return $this->hasDurationMinutesColumn() && $reservation->duration_minutes !== null
            ? (int) $reservation->duration_minutes
            : (int) $reservation->hour * 60;
    }

    private function hasDurationMinutesColumn(): bool
    {
        return $this->hasDurationMinutesColumn ??= Schema::hasColumn('reservations', 'duration_minutes');
    }

    private function completedMinutesByStudentIds(array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $now = now();
        $fallbackMinutes = 'COALESCE(reservations.hour, 0) * 60';
        $durationMinutes = $this->hasDurationMinutesColumn()
            ? "COALESCE(reservations.duration_minutes, {$fallbackMinutes})"
            : $fallbackMinutes;
        $elapsedMinutes = "CASE
            WHEN reservations.start_at IS NOT NULL AND reservations.end_at IS NOT NULL THEN
                CASE
                    WHEN reservations.end_at > reservations.start_at THEN TIME_TO_SEC(TIMEDIFF(reservations.end_at, reservations.start_at)) / 60
                    ELSE TIME_TO_SEC(TIMEDIFF(reservations.end_at, reservations.start_at)) / 60 + 1440
                END
            ELSE {$durationMinutes}
        END";

        // This aggregation purposely stays in SQL. Loading every historical
        // reservation as Eloquent models made a staff dashboard login slow on
        // agencies with a long lesson history.
        return DB::table('reservations')
            ->join('trainings', 'trainings.reservation_id', '=', 'reservations.id')
            ->leftJoin('review_monitors', 'review_monitors.reservation_id', '=', 'reservations.id')
            ->whereNull('reservations.deleted_at')
            ->whereNull('trainings.deleted_at')
            ->where('reservations.is_active', true)
            ->whereIn('trainings.student_id', $studentIds)
            ->where(function ($query) use ($now) {
                $query->whereDate('reservations.date', '<', $now)
                    ->orWhere(function ($today) use ($now) {
                        $today->whereDate('reservations.date', '=', $now)
                            ->whereTime('reservations.end_at', '<', $now->format('H:i'));
                    });
            })
            ->where(function ($query) {
                $query->whereNull('review_monitors.is_absent')
                    ->orWhere('review_monitors.is_absent', false);
            })
            ->select('trainings.student_id')
            ->selectRaw("SUM({$elapsedMinutes}) as completed_minutes")
            ->groupBy('trainings.student_id')
            ->pluck('completed_minutes', 'trainings.student_id')
            ->map(fn ($minutes) => (int) round($minutes))
            ->all();
    }
}
