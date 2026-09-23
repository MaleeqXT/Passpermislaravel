<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Reservation;

use App\Http\Controllers\Controller;
use  App\Repository\V2\Monitor\Schedule\Reservation\Job\StoreUnrestrictiveReservationRepo;

use App\Repository\V2\Monitor\Schedule\Reservation\Job\StoreReservationRepo;
use App\Services\Student\Training\info\TrainingInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use App\Http\Requests\V1\Student\Schedule\Reservation\StoreReservationRequest;
use App\Http\Requests\V1\Student\Schedule\Reservation\UpdateReservationRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Http\Requests\V1\Student\Schedule\StoreOrUpdateScheduleRequest;
use App\Models\Roles\Monitor\Schedule\Reservation;

use Illuminate\Support\Facades\Log;
use App\Models\TrainingUnrestricted;
use App\Models\ReservationUnrestricted;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin\FetchByMonthAllReservationUnrestrictedRepo;
use Illuminate\Http\JsonResponse;


class UnrestrictiveReservationAdminController extends Controller
{
   public function index(Request $request): JsonResponse
    {
        // Return reservations grouped by date - check both tables
        $query = Reservation::query()
            ->with(['monitor', 'lieu', 'training.student', 'training.offer'])
            ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef'));

        if ($request->has('start') && $request->has('end')) {
            $query->whereBetween('date', [$request->input('start'), $request->input('end')]);
        }

        if ($request->has('monitor_id')) {
            $monitorIds = $request->input('monitor_id');
            if (is_array($monitorIds)) {
                $query->whereIn('monitor_id', $monitorIds);
            } else {
                $query->where('monitor_id', $monitorIds);
            }
        }

        if ($request->has('lieu_id')) {
            $query->where('lieu_id', $request->input('lieu_id'));
        }

        $data = $query->orderBy('monitor_id')->get()->groupBy('datef');

        return response()->json([
            'data' => $data,
        ]);
    }


public function store(StoreReservationRequest $request)
{
    DB::beginTransaction();

    try {
        // ✅ Step 1: Create main reservation in reservations table
        $reservation = Reservation::create([
            'id'         => Str::uuid(),
            'monitor_id' => $request->monitor_id,
            'date'       => $request->date,
            'start_at'   => $request->start_at,
            'end_at'     => $request->end_at,
            'hour'       => $request->hour ?? 1,
            'is_active'  => $request->is_active ?? 1,
            'lieu_id'    => $request->lieu_id,
            'color'      => $request->color,
        ]);

        // ✅ Step 2: Create training record
        $training = \App\Models\Roles\Student\Schedule\Training::create([
            'id'             => Str::uuid(),
            'student_id'     => $request->student_id,
            'reservation_id' => $reservation->id,
            'offer_id'       => $request->offer_id,
        ]);

        // ✅ Step 3: Mirror to unrestricted tables (optional)
        try {
            ReservationUnrestricted::create([
                'id'         => $reservation->id,
                'monitor_id' => $reservation->monitor_id,
                'date'       => $reservation->date,
                'start_at'   => $reservation->start_at,
                'end_at'     => $reservation->end_at,
                'hour'       => $reservation->hour,
                'is_active'  => $reservation->is_active,
                'lieu_id'    => $reservation->lieu_id,
                'color'      => $reservation->color,
            ]);

            TrainingUnrestricted::create([
                'id'             => $training->id,
                'student_id'     => $training->student_id,
                'reservation_id' => $reservation->id,
                'offer_id'       => $training->offer_id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to mirror to unrestricted tables', ['error' => $e->getMessage()]);
        }

        DB::commit();

        session()->flash('success', 'Reservation created successfully.');

        return response()->json([
            'reservation' => $reservation,
            'training'    => $training,
        ], 201);

        } catch (\Throwable $e) {
        DB::rollBack();

        \Log::error('Failed to create unrestricted records', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'error' => 'Failed to create unrestricted records.',
            'message' => $e->getMessage(),
        ], 500);
    }
}





    public function show($id)
    {
        $reservation = ReservationUnrestricted::with(['monitor', 'lieu', 'trainings.student', 'trainings.offer'])->find($id);
        
        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }
        
        return response()->json($reservation);
    }





 public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
        // ✅ Step 1: Update main reservation in reservations table
        $reservation = Reservation::findOrFail($id);
        
        $reservation->update([
            'monitor_id' => $request->monitor_id ?? $reservation->monitor_id,
            'date'       => $request->date ?? $reservation->date,
            'start_at'   => $request->start_at ?? $reservation->start_at,
            'end_at'     => $request->end_at ?? $reservation->end_at,
            'hour'       => $request->hour ?? $reservation->hour,
            'is_active'  => $request->is_active ?? $reservation->is_active,
            'lieu_id'    => $request->lieu_id ?? $reservation->lieu_id,
            'color'      => $request->color ?? $reservation->color,
        ]);

        // ✅ Step 2: Update or create unrestricted reservation (mirror)
        $reservationUnrestricted = ReservationUnrestricted::find($id);
        
        if (!$reservationUnrestricted) {
            // If unrestricted reservation doesn't exist, create it
            $reservationUnrestricted = ReservationUnrestricted::create([
                'id'         => $id,
                'monitor_id' => $reservation->monitor_id,
                'date'       => $reservation->date,
                'start_at'   => $reservation->start_at,
                'end_at'     => $reservation->end_at,
                'hour'       => $reservation->hour,
                'is_active'  => $reservation->is_active,
                'lieu_id'    => $reservation->lieu_id,
                'color'      => $reservation->color,
            ]);
        } else {
            // Update existing unrestricted reservation
            $reservationUnrestricted->update([
                'monitor_id' => $reservation->monitor_id,
                'date'       => $reservation->date,
                'start_at'   => $reservation->start_at,
                'end_at'     => $reservation->end_at,
                'hour'       => $reservation->hour,
                'is_active'  => $reservation->is_active,
                'lieu_id'    => $reservation->lieu_id,
                'color'      => $reservation->color,
            ]);
        }

        // ✅ Step 3: Update or create unrestricted training
        $trainingUnrestricted = TrainingUnrestricted::where('reservation_id', $reservationUnrestricted->id)->first();

        if ($trainingUnrestricted) {
            // Update existing training
            $trainingUnrestricted->update([
                'student_id' => $request->student_id ?? $trainingUnrestricted->student_id,
                'offer_id'   => $request->offer_id ?? $trainingUnrestricted->offer_id,
            ]);
            } else {
            // Create new unrestricted training (if provided)
                if (!empty($request->student_id) && !empty($request->offer_id)) {
                $trainingUnrestricted = TrainingUnrestricted::create([
                    'id'             => Str::uuid(),
                        'student_id'     => $request->student_id,
                    'reservation_id' => $reservationUnrestricted->id,
                        'offer_id'       => $request->offer_id,
                    ]);
                }
            }

            DB::commit();

        session()->flash('success', '✅ Reservation & Training unrestricted records updated successfully.');

        return response()->json([
            'reservation' => $reservation,
            'training'    => $trainingUnrestricted ?? null,
        ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

        Log::error('Failed to update unrestricted records', [
            'error' => $e->getMessage(),
            'id'    => $id,
        ]);

        session()->flash('error', '❌ Failed to update unrestricted records: ' . $e->getMessage());

        return response()->json([
            'error'   => 'Failed to update unrestricted records.',
            'message' => $e->getMessage(),
        ], 500);
        }
    }




    /**
     * ✅ Delete unrestricted reservation + training
     */
    public function destroy($id)
    {
        $reservation = ReservationUnrestricted::find($id);
        
        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }

        // Also delete related training unrestricted
        TrainingUnrestricted::where('reservation_id', $reservation->id)->delete();

        $reservation->delete();

        return response()->json(['deleted' => true]);
    }
}


