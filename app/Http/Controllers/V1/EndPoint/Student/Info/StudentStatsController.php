<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;


use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\CountCompetencyRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchReservationDashboardRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\SumHoursTrainingStudentRepo;
use Illuminate\Http\JsonResponse;

class StudentStatsController extends Controller
{

    /**
     * @param Student $student
     * @param SumHoursTrainingStudentRepo $repo
     * @return JsonResponse
     */
    public function index(Student $student, SumHoursTrainingStudentRepo $repo): JsonResponse
    {

        return response()->json([
            'data' => [
                'id' => $student->id, // add student id to the response to prevent recurring request
                'student' => [
                    'id' => $student->id,
                    'balance' => (float) $student->balance,
                    // 0 = boîte manuelle (BM), 1 = boîte automatique (BA).
                    // The React offer catalogue uses this to show only matching offers.
                    'boite_type' => $student->boite_type === null ? null : (int) $student->boite_type,
                    'date_code' => $student->date_code,
                    'required_documents' => $student->required_documents ?? [],
                    'user' => [
                        'id' => $student->user?->id,
                        'name' => $student->user?->name,
                        'first_name' => $student->user?->first_name,
                        'last_name' => $student->user?->last_name,
                        'email' => $student->user?->email,
                        'media' => $student->user?->media,
                        'phone' => $student->user?->phone,
                        'sexe' => $student->user?->sexe,
                        'date_naissance' => $student->user?->date_naissance,
                        'adresse' => $student->user?->adresse,
                        'postal' => $student->user?->postal,
                        'postal_code_1' => $student->user?->postal_code_1,
                        'ville' => $student->user?->ville,
                    ],
                ],
                'reservations' => [
                    'passed' => +$passed = $repo->run(['student_id' => $student->id, 'is_passed' => true]),
                    'upcoming' => +$upcoming = $repo->run(['student_id' => $student->id, 'is_coming' => true]),
                ],
                'balance' => [
                    'rest' => +$student->wallets()->sum('balance') ?? 0,
                    'used' => $passed + $upcoming,
                ],
                'contract' => [
                    'available' => $student->canViewContract(),
                ],
                'competences' => [
                    // 'total' => CountCompetencyRepo::run($student),
                    'done' => CountCompetencyRepo::run($student, ['is_done' => true])
                ],
            ]
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function upcomingTraining(): JsonResponse
    {
        return response()->json([
            'data' => FetchReservationDashboardRepo::run([
                'monitor_id' => auth()->user()->monitor->id,
            ])
        ]);
    }
}
