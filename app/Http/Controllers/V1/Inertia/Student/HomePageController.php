<?php

namespace App\Http\Controllers\V1\Inertia\Student;

use App\Http\Controllers\Controller;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\CountCompetencyRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\SumHoursTrainingStudentRepo;
use App\Repository\V2\Student\Schedule\Training\Job\FetchPrimaryDataOfStudentTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Job\FetchSumHourTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\FetchSumStudentWalletRepo;
use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;


class HomePageController extends Controller
{

    /**
     * 
     */
    public function index()
    {
        $student = Auth::user()?->student;

        $studentContract = $student
            ? FetchPrimaryDataOfStudentTrainingRepo::run([
                'student_id' => $student->id
            ])
            : null;

        return response()->json([
            'studentContract' => $studentContract,
            'dashboardProgress' => $student ? $this->dashboardProgress($student) : null,
        ]);
    }

    /**
     * Progress data for the authenticated student's React dashboard.
     *
     * There is no reliable Code/Conduite grouping in the current competency
     * structure. Until one is supplied, both cards deliberately expose the
     * same generic competency score and mark that fallback in the response.
     */
    private function dashboardProgress($student): array
    {
        $wallets = $student->wallets()
            ->where('status', WalletTypeEnum::ACTIVE->value)
            ->with('offer:id,balance')
            ->get();

        // Installments may create several wallet rows for one offer. Use each
        // active offer once so its allocated hours are never counted twice.
        $activeWallets = $wallets
            ->filter(fn($wallet) => $wallet->offer)
            ->values();

        // A student may have several wallet rows for one offer (for example,
        // installments or a later hour purchase). Hours are counted once per
        // offer, but every wallet balance remains part of their entitlement.
        $offerIds = $activeWallets->pluck('offer_id')->unique()->values()->all();
        $hoursCompleted = empty($offerIds)
            ? 0
            : SumHoursTrainingStudentRepo::run([
                'student_id' => $student->id,
                'offer_ids' => $offerIds,
                'is_passed' => true,
            ]);
        $hoursRemaining = (int) $activeWallets->sum(fn($wallet) => max(0, (int) ($wallet->balance ?? 0)));

        // Wallet.balance is the current, remaining entitlement and is updated
        // for every reservation. There is no immutable purchased-hours column;
        // therefore completed + remaining is the reliable package total. Using
        // offers.balance here was incorrect for top-ups and multiple purchases.
        $hoursTotal = (int) $hoursCompleted + $hoursRemaining;
        $competencies = CountCompetencyRepo::progress($student);

        return [
            'code' => [
                'percent' => $competencies['percent'],
                'mapping_available' => false,
            ],
            'conduct' => [
                'percent' => $competencies['percent'],
                'mapping_available' => false,
            ],
            'competencies' => $competencies,
            'hours' => [
                'completed' => (int) $hoursCompleted,
                'total' => $hoursTotal,
                'remaining' => $hoursRemaining,
            ],
            'upcoming_appointments' => $this->upcomingAppointments($student),
            'recent_courses' => $this->recentCourses($student),
        ];
    }

    /**
     * The next lessons booked by the authenticated student.
     *
     * Reservations and trainings both use soft deletes, so deleted records
     * are automatically excluded by their model scopes.
     */
    private function upcomingAppointments($student): array
    {
        return Reservation::query()
            ->with([
                'training:id,student_id,reservation_id,session_type,prestation',
                'monitor.user:id,name,first_name,last_name',
                'lieu.zone:id,name',
            ])
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->isComme()
            ->orderBy('date')
            ->orderBy('start_at')
            ->limit(2)
            ->get()
            ->map(function (Reservation $reservation) {
                $monitor = $reservation->monitor?->user;
                $monitorName = $monitor?->name
                    ?: trim(implode(' ', array_filter([$monitor?->first_name, $monitor?->last_name])));
                $zoneName = $reservation->lieu?->zone?->name;

                return [
                    'id' => $reservation->id,
                    'date' => $reservation->date?->toDateString(),
                    'start_at' => $reservation->start_at?->format('H:i'),
                    'title' => $reservation->training?->prestation
                        ?: $reservation->training?->session_type
                        ?: 'Leçon de conduite',
                    'monitor_name' => $monitorName ?: null,
                    'location' => $zoneName
                        ? "Agence {$zoneName}"
                        : ($reservation->lieu?->name ?: 'Lieu à confirmer'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * The three most recent completed lessons for the authenticated student.
     */
    private function recentCourses($student): array
    {
        return Reservation::query()
            ->with('training:id,student_id,reservation_id,session_type,prestation')
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->isPassed()
            ->orderByDesc('date')
            ->orderByDesc('start_at')
            ->limit(3)
            ->get()
            ->map(fn (Reservation $reservation) => [
                'id' => $reservation->id,
                'date' => $reservation->date?->toDateString(),
                'start_at' => $reservation->start_at?->format('H:i'),
                'hour' => (float) ($reservation->hour ?? 0),
                'title' => $reservation->training?->prestation
                    ?: $reservation->training?->session_type
                    ?: 'Leçon de conduite',
            ])
            ->values()
            ->all();
    }

    // public function index()
    // {
    //     $student = Auth::user()?->student;
    //     $data=FetchPrimaryDataOfStudentTrainingRepo::run(['student_id' => $student->id]);
    //     // return $data;

    //     return Inertia::render(
    //         'features/dashboard/DashboardPage',
    //         [
    //             'studentContract' => $student
    //                 ? FetchPrimaryDataOfStudentTrainingRepo::run(['student_id' => $student->id])
    //                 : null,
    //             // 'lessons' => FetchCoursRepo::run(request()->all()),
    //             // 'balance' => [
    //             //     'dispo' => FetchSumStudentWalletRepo::run(auth()->user()->student),
    //             //     'used' => FetchSumHourTrainingRepo::run(['student_id' => auth()->user()->student->id])
    //             // ],
    //             // 'progress' => [
    //             //     'total' => CountCompetencyRepo::run(auth()->user()->student),
    //             //     'done' => CountCompetencyRepo::run(auth()->user()->student, ['is_done' => true])
    //             // ],
    //             // 'enseignants' => FetchAllMonitorDataTrainingRepo::run(['student_id' => auth()->user()->student->id])
    //         ]
    //     );
    // }
}
