<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Roles\Student\Schedule\Cancellation;
use App\Repository\V2\Student\Schedule\Training\Cancellation\EditCancellationRepo;
use App\Repository\V2\Student\Schedule\Training\Cancellation\FetchAllCancellationRepo;
use App\Services\Student\Training\Cancellation\CancellationInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class AdminCancellationController extends Controller
{


    /**
     * 
     */
    public function index()
    {
        $zoneId = auth()->user()->zone_id;
        $filters = array_merge(request()->all(), [
            'zone_id' => $zoneId,
        ]);

        $baseQuery = Cancellation::query()
            ->whereHas('training.student.user', fn($query) => $query->where('zone_id', $zoneId));

        return response()->json([
            'cancellations' => FetchAllCancellationRepo::run($filters),
            'counts' => [
                'all_count' => (clone $baseQuery)->count(),
                'is_justified_0_count' => (clone $baseQuery)->where('is_justified', 0)->count(),
                'is_justified_1_count' => (clone $baseQuery)->where('is_justified', 1)->count(),
            ],
        ]);
    }

    // public function index(): Response
    // {

    //     return Inertia::render('features/general/cancellations/CancellationsPage', [
    //         'cancellations' => FetchAllCancellationRepo::run(request()->all())
    //     ]);
    // }


    /**
     * @param Cancellation $cancellation
     * @param CancellationInterface $service
     * @return RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function update(Cancellation $cancellation, CancellationInterface $service): RedirectResponse|JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = [
                'status' => request()->get('status'),
                'is_justified' => request()->get('is_justified') ?? false,
            ];
            if (request()->get('status') == CancellationStatusEnum::SUCCESS_CANCELLED->value) {
                $data['is_justified'] = true;
            }


            $service->EditCancellation($cancellation, $data);
            // EditCancellationRepo::run($cancellation, $data);
            DB::commit();
            session()->flash('success', flashMessage());
            // return

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Le statut de l’annulation a été mis à jour.',
                    'cancellation' => $cancellation->fresh(),
                ]);
            }

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
