<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Schedule\StoreManyScheduleRequest;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\User\Monitor;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\CountByMonthAllReservationByMonitorRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchAllReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchByTodayAllMonitorReservationRepo;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReservationMonitorController extends Controller
{


    public function __construct(public ReservationInterface $service) {}

    /**
     *   /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'reservations' => FetchByTodayAllMonitorReservationRepo::run(request()->all()),
        ]);
    }

    /**
     *   /**
     * @return JsonResponse
     */
    public function schedule(): JsonResponse
    {
        $user = auth()->user();
        $monitor = $user?->monitor
            ?? Monitor::query()->where('user_id', $user?->id)->first();

        // An administrator can explicitly inspect one monitor's calendar.
        // A monitor account always remains restricted to its own profile.
        if (!$monitor && $user?->hasAnyRole(['admin', 'super-admin']) && request()->filled('monitor_id')) {
            $targetId = request()->input('monitor_id');
            $monitor = Monitor::query()
                ->whereKey($targetId)
                ->orWhere('user_id', $targetId)
                ->first();
        }

        // A monitor schedule is private: never return every monitor's slots
        // when the logged-in account has no monitor profile.
        if (!$monitor) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => FetchAllReservationRepo::run([
                ...request()->all(),
                'monitor_id' => $monitor->id,
            ], ['training.student.reviewMonitor'])->groupBy(['datef'])
        ]);
    }

    /**
     * @param CountByMonthAllReservationByMonitorRepo $repo
     * @return Response
     */
    public function events()
    {
        return response()->json([
            'data' => CountByMonthAllReservationByMonitorRepo::run(['monitor_id' => [auth()->user()->monitor->id]] + request()->all())
        ]);
    }

    /**
     * @param StoreManyScheduleRequest $request
     * @return JsonResponse
     * @throws Exception
     */
  
  
public function storeMany(StoreManyScheduleRequest $request): JsonResponse
{
    try {
        $validated = $request->validated();
        $user = auth()->user();
        // API authentication does not always eager-load the monitor relation.
        $monitor = $user?->monitor
            ?? Monitor::query()->where('user_id', $user?->id)->first();
        $monitor_id = $monitor?->id;

        if (!$monitor && $user?->hasAnyRole(['admin', 'super-admin']) && !empty($validated['monitor_id'])) {
            $targetId = $validated['monitor_id'];
            $monitor = Monitor::query()
                ->whereKey($targetId)
                ->orWhere('user_id', $targetId)
                ->first();
            $monitor_id = $monitor?->id;
        }

        if (!$monitor_id) {
            return response()->json([
                'message' => 'La disponibilité doit être créée depuis le compte du moniteur concerné.',
            ], 422);
        }

        $reservations = $validated['data'];
        $requestedLieuId = $validated['lieu_id'] ?? null;
        $requestedLieu = Lieu::query()->find($requestedLieuId);

        if ($requestedLieu && !$monitor->lieux()->whereKey($requestedLieu->id)->exists()) {
            return response()->json([
                'message' => 'Ce lieu n’est pas associé à votre profil moniteur.',
            ], 422);
        }

        $lieu_id = $requestedLieu?->id
            ?? $monitor->lieux()->value('lieux.id')
            ?? Lieu::query()->where('zone_id', $user?->zone_id)->value('id');

        if (!$lieu_id) {
            return response()->json([
                'message' => 'Aucun lieu valide n’est disponible pour créer cette disponibilité.',
            ], 422);
        }

        $created = [];

        foreach ($reservations as $reservation) {
            $reservation['monitor_id'] = $monitor_id;
            $reservation['lieu_id']    = $lieu_id;
            $reservation['is_active'] = $reservation['is_active'] ?? true;
            $reservation['color'] = $reservation['color'] ?? '#2dd881';
            $reservation['hour'] = $reservation['hour'] ?? max(1, (int) ceil(
                \Carbon\Carbon::createFromFormat('H:i', $reservation['start_at'])
                    ->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i', $reservation['end_at'])) / 60
            ));

            // ✅ sirf create karo (notification skip)
            $new = Reservation::create($reservation);

            $created[] = $new;
        }

        return response()->json([
            'message' => 'Reservations created successfully',
            'count'   => count($created),
            'data'    => $created
        ], 201);

    } catch (Exception $e) {
        report($e);
        return response()->json([
            'message' => 'Impossible de créer la disponibilité.',
            'details' => app()->environment('local') ? $e->getMessage() : null,
        ], 422);
    }
}


    /**
     * @param Reservation $reservation
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        DB::beginTransaction();
        try {
            // delete user
            $this->service->delete($reservation);

            DB::commit();
            return response()->json(['message' => 'Operation effectuee avec succes'], 200);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la suppression du Planning');
            throw $e;
        }
    }
}
