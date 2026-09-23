<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Schedule\Training\StoreOrUpdateTrainingRequest;
use App\Services\Student\Training\info\TrainingInterface;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    // Temporary switch: set to false to re-enable student reservations.
    private const RESERVATIONS_TEMPORARILY_UNAVAILABLE = true;

    public function __construct(public TrainingInterface $service) {}

    /**
     * @return Response
     */


    public function index(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/reservations/ReservationsPage');
    }

    /**
     * @param StoreOrUpdateTrainingRequest $request
     * @param ReservationInterface $planningInterface
     * @return RedirectResponse
     * @throws Exception
     */
public function store(StoreOrUpdateTrainingRequest $request, ReservationInterface $planningInterface): RedirectResponse
{
    DB::beginTransaction();
    try {
        if (self::RESERVATIONS_TEMPORARILY_UNAVAILABLE) {
            throw new Exception('Les réservations sont temporairement indisponibles.');
        }

        $student = auth()->user()?->student;

        Log::info('🔵 TrainingController.store() called');
        Log::info('Request data:', $request->all());
        Log::info('Student:', ['id' => $student->id, 'boite_type' => $student->boite_type]);

        // ✅ Weekly limit check (use requested date if provided)
        $this->checkWeeklyReservationLimit($student, $request->input('date'));

        // ✅ Random available planning
        $requestData = $request->only(['date', 'start_at', 'end_at', 'offer_id', 'lieu_id', 'monitor_id']);
        Log::info('Calling getRandom with:', $requestData);

        $reservation = $planningInterface->getRandom(
            $requestData,
            $student
        );

        Log::info('✅ Reservation found:', ['reservation_id' => $reservation->id, 'monitor_id' => $reservation->monitor_id]);

        if (!$reservation) {
            throw new Exception("Aucun créneau disponible pour cette sélection.");
        }

        // ✅ Check if reservation already assigned to another student
        if (!empty($reservation->training) && $reservation->training->student_id !== $student->id) {
            throw new Exception("Cet horaire est déjà réservé par un autre étudiant.");
        }

        // ✅ Create training session
        $this->service->create(
            $request->only(['offer_id', 'hour']),
            $reservation
        );

        DB::commit();
        Log::info('✅ Training created successfully');
        session()->flash('success', flashMessage());
        return redirect()->back();

    } catch (Exception $e) {
        DB::rollback();
        Log::error('❌ Training Error:', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()]);
        session()->flash('error', flashMessage('error', $e->getMessage()));
        return redirect()->back();
    }
}

    /**
     * Check if student has exceeded weekly reservation limit (2 reservations per week)
     */
    private function checkWeeklyReservationLimit($student, $date = null)
    {
        try {
            Log::info('📅 checkWeeklyReservationLimit() called');
            $reference = $date ? Carbon::parse($date) : now();
            $weekStart = $reference->copy()->startOfWeek();
            $weekEnd = $reference->copy()->endOfWeek();
            Log::info('Week range:', ['start' => $weekStart, 'end' => $weekEnd, 'reference_date' => $reference]);

            // Get total reservations already made this week
            $weeklyReservations = $student->trainings()
                ->whereHas('reservation', function($query) use ($weekStart, $weekEnd) {
                    $query->whereBetween('date', [$weekStart, $weekEnd]);
                })
                ->count();

            Log::info('Weekly reservations count:', ['count' => $weeklyReservations]);

            if ($weeklyReservations >= 2) {
                throw new Exception("Vous ne pouvez faire que 2 réservations par semaine. Vous avez déjà fait {$weeklyReservations} réservation(s) cette semaine.");
            }
            Log::info('✅ Weekly limit check PASSED');
        } catch (Exception $e) {
            Log::error('❌ Weekly limit check ERROR:', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}







