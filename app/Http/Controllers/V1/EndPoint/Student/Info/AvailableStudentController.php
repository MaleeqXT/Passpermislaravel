<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;


use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Schedule\Training\StoreAvailableTrainingStudentRequest;
use App\Models\Roles\Student\Schedule\StudentAvailability;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin\FetchByWeekAllStudentReservationRepo;
use App\Repository\V2\Student\Schedule\Training\Available\DestroyAvailableTrainingStudentRepo;
use App\Repository\V2\Student\Schedule\Training\Available\FetchByWeekAllAvailableTrainingStudentRepo;
use App\Repository\V2\Student\Schedule\Training\Available\StoreAvailableTrainingStudentRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AvailableStudentController extends Controller
{

    /**
     * @param Student $student
     * @return JsonResponse
     */
    public function index(Student $student): JsonResponse
    {
        return response()->json(['availables' => FetchByWeekAllAvailableTrainingStudentRepo::run($student, request()->all())]);
    }

    public function getAllReservationsGroupedByWeek(Student $student): JsonResponse
    {
        return response()->json(['reservations' => FetchByWeekAllStudentReservationRepo::run($student, request()->all())]);
    }


    /**
     * @param StoreAvailableTrainingStudentRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function store(StoreAvailableTrainingStudentRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // create new Disponibilité
            $studentAvailability = StoreAvailableTrainingStudentRepo::run($request->validated());

            DB::commit();
            session()->flash('success', 'Le Disponibilité est bien Ajouter');
            return response()->json($studentAvailability);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la création du Disponibilité');
            throw $e;
        }
    }


    /**
     * @param StudentAvailability $studentAvailability
     * @param DestroyAvailableTrainingStudentRepo $action
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(StudentAvailability $studentAvailability): JsonResponse
    {
        DB::beginTransaction();
        try {

            // delete Disponibilité
            $studentAvailability = DestroyAvailableTrainingStudentRepo::run($studentAvailability);

            DB::commit();

            session()->flash('success', 'Le Disponibilité est bien Supprimer');
            return response()->json($studentAvailability);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la suppression du Disponibilité');
            throw $e;
        }
    }
}
