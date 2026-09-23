<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Schedule\Reservation\StoreReservationRequest;
use App\Http\Requests\V1\Student\Schedule\Reservation\UpdateReservationRequest;
use App\Http\Requests\V1\Student\Schedule\StoreOrUpdateScheduleRequest;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Notifications\V1\Monitor\Training\Cancellation\CancellationTrainingMonitorNotification;
use App\Notifications\V1\Student\Training\Cancellation\CancellationTrainingStudentNotification;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin\FetchByMonthAllReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin\FetchByWeekAllReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\CountByMonthAllReservationByMonitorRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\DestroyReservationRepo;
use App\Repository\V2\Student\Schedule\Training\Job\DestroyTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\IncOrDecWalletRepo;
use App\Services\Student\Training\Cancellation\CancellationInterface;
use App\Services\Student\Training\info\TrainingInterface;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Enums\V2\Student\Schedule\Wallet\WalletStatusEnum;
use Throwable;
class ReservationAdminController extends Controller
{

    /**
     * @param StoreOrUpdateScheduleRequest $request
     * @return JsonResponse
     */


    public function index(StoreOrUpdateScheduleRequest $request): JsonResponse
    {
        //dd(request()->all());
        $data = $request->get('view') === 'month' ? FetchByMonthAllReservationRepo::run($request->validated()) : FetchByWeekAllReservationRepo::run($request->validated());
    //   dd($data);
        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function events()
    {
        return response()->json([
            'data' => CountByMonthAllReservationByMonitorRepo::run(request()->all())
        ]);
    }

//     public function store(
//     StoreReservationRequest $request,
//     ReservationInterface $servicePlanning,
//     TrainingInterface $service
// ) {

 



//         $reservation = $servicePlanning->create(
//             $request->except('student_id', 'offer_id')
//         );

//        $service= $service->create(
//             $request->only('student_id', 'offer_id', 'hour'),
//             $reservation
//         );

//         return $service;
          

   

//         return response()->json([
//             'success' => true,
//             'message' => 'Le Planning a été ajouté avec succès.',
//             'data' => $reservation
//         ]);

    


//         return response()->json([
//             'success' => false,
//             'message' => 'Une erreur est survenue.'
//         ], 500);
    
// }


    /**
     * @param StoreReservationRequest $request
     * @param ReservationInterface $servicePlanning
     * @param TrainingInterface $service
     * @return JsonResponse
     * @throws Exception
     */
    public function store(
        StoreReservationRequest $request,
        ReservationInterface $servicePlanning,
        TrainingInterface $service
    ): JsonResponse {

    
        DB::beginTransaction();

        try {

            $reservation = $servicePlanning->create(
                // These two values belong exclusively to the related training.
                // Passing them here attempts to insert them in `reservations`.
                $request->except('student_id', 'offer_id', 'session_type', 'prestation')
            );

            $service->create(
                $request->only('student_id', 'offer_id', 'hour', 'session_type', 'prestation'),
                $reservation
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Le Planning a été ajouté avec succès.',
                'data' => $reservation
            ]);

        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Erreur lors de la création du planning', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            if (in_array($e->getMessage(), ['E0001', 'E0004'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() === 'E0004'
                        ? "Le candidat n'a pas assez d'heures disponibles pour cette offre."
                        : 'Ce moniteur a déjà une réservation sur ce créneau.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * @param Reservation $reservation
     * @param UpdateReservationRequest $request
     * @param TrainingInterface $service
     * @param ReservationInterface $reservationService
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Reservation $reservation, UpdateReservationRequest $request, TrainingInterface $service, ReservationInterface $reservationService): JsonResponse
    {
        DB::beginTransaction();
        try {
            $originalReservationHour = (int) ($reservation->hour ?? 0);

            // Validate update with conflict checks
            $reservationService->update($reservation, $request->only(['date', 'start_at', 'end_at', 'monitor_id', 'lieu_id', 'color', 'hour']));

            if ($reservation?->training) {
                $service->update($reservation?->training, array_merge(
                    $request->validated(),
                    ['previous_hour' => $originalReservationHour]
                ));
            }
            else {
                $service->create($request->only('student_id', 'offer_id', 'hour', 'session_type', 'prestation'), $reservation);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Le Planning est bien Modifier',
                'data' => $reservation
            ]);
        } catch (Exception $e) {
            DB::rollback();

            // Check if it's a reservation conflict error
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'réservation') || str_contains($msg, 'disponibilité') || str_contains($msg, 'create_planning_existe')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La réservation ou la disponibilité existe déjà.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la modification du Planning'
            ], 500);
        }
    }

    /**
     * @param Reservation $reservation
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Reservation $reservation)
    {
        DB::beginTransaction();
        try {
            // delete user
            $training = $reservation->training;
            if ($training) {
                IncOrDecWalletRepo::run($training->student, $training->offer_id, WalletStatusEnum::INCREMENT->value, $training?->reservation?->hour);
                // send mail to monitor
                $user = $reservation?->monitor?->user;
                if ($user?->email) {
                    $this->sendNotificationSafely(
                        $user->email,
                        new CancellationTrainingMonitorNotification($user, $reservation),
                        'admin reservation delete monitor'
                    );
                }

                // send mail to Student
                $user = $training->student?->user;
                if ($user?->email) {
                    $this->sendNotificationSafely(
                        $user->email,
                        new CancellationTrainingStudentNotification($user, $reservation),
                        'admin reservation delete student'
                    );
                }

                // delete session
                DestroyTrainingRepo::run($training);
            }
            $status = DestroyReservationRepo::run($reservation);
            DB::commit();
            return response()->json([
                'message' => 'Operation effectuee avec succes',
                'status' => $status
            ]);
        } catch (Exception $e) {
            DB::rollback();
            // session()->flash('error', 'Une erreur est survenue lors de la suppression du Planning');
            throw $e;
        }
    }

    /**
     * @param Reservation $reservation
     * @param CancellationInterface $annulationInterface
     * @return JsonResponse
     * @throws Exception
     */
    public function CancellationTraining(Reservation $reservation, CancellationInterface $annulationInterface)
    {
        DB::beginTransaction();
        try {
            // delete user
            $training = $reservation->training;

            $status = $annulationInterface->CancellationTraining($training);
            DB::commit();
            return response()->json([
                'message' => 'Operation effectuee avec succes',
                'status' => $status
            ]);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la suppression du Planning');
            throw $e;
        }
    }

    protected function sendNotificationSafely(string $email, object $notification, string $context): void
    {
        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (Throwable $e) {
            Log::error('Reservation admin notification failed', [
                'context' => $context,
                'email' => $email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
