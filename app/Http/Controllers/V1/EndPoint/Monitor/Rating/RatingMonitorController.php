<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Rating;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Schedule\UpdateRatingMonitorRequest;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Repository\V2\Monitor\Schedule\Reservation\Review\EditReviewRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Review\FetchAllReviewMonitorRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Review\StoreReviewRepo;
use App\Services\SatisfactionOfferTriggerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RatingMonitorController extends Controller
{


    /**
     *   /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): JsonResponse
    {
        return response()->json(FetchAllReviewMonitorRepo::run(request()->all(), false));
    }

    public function store(Reservation $reservation, UpdateRatingMonitorRequest $request)
    {
        DB::beginTransaction();
        try {
            $review = StoreReviewRepo::run($reservation, $request->validated());
            DB::commit();
            if ($review) {
                app(SatisfactionOfferTriggerService::class)->processCompletedLesson($review);
            }
            return response()->json(['message' => 'Operation effectuee avec succes']);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la suppression du Review');
            throw $e;
        }
    }

    /**
     * @param ReviewMonitor $reviewMonitor
     * @param UpdateRatingMonitorRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function update(ReviewMonitor $reviewMonitor, UpdateRatingMonitorRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $updated = EditReviewRepo::run($reviewMonitor, $request->validated());
            DB::commit();
            if ($updated) {
                app(SatisfactionOfferTriggerService::class)->processCompletedLesson($reviewMonitor->fresh());
            }
            return response()->json(['message' => 'Operation effectuee avec succes']);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la suppression du Review');
            throw $e;
        }
    }
}
