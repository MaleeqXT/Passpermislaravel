<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Schedule\Training\StoreOrUpdateCancellationRequest;
use App\Models\Roles\Student\Schedule\Cancellation;
use App\Repository\V2\Shared\Base\Media\EditMediaRepo;
use App\Repository\V2\Student\Schedule\Training\Cancellation\EditCancellationRepo;
use App\Repository\V2\Student\Schedule\Training\Cancellation\FetchAllCancellationRepo;
use App\Services\Student\Training\Cancellation\CancellationInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentCancellationController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('features/settings/cancellations/CancellationsPage', [
            // 'cancellations' => FetchAllCancellationRepo::run(request()->all())
        ]);
    }

    /**
     * @param CancellationInterface $service
     * @param StoreOrUpdateCancellationRequest $request
     * 
     * @throws ValidationException|Exception
     */

    public function store(CancellationInterface $service, StoreOrUpdateCancellationRequest $request)
{
    DB::beginTransaction();

    try {
        $service->TryCancellation($request->validated());

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Cancellation created successfully.',
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => flashMessage('error'),
            'error' => $e->getMessage(), // Production mein is line ko hata dena
        ], 500);
    }
}

    // public function store(CancellationInterface $service, StoreOrUpdateCancellationRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         $service->TryCancellation($request->validated());
    //         DB::commit();
    //         return redirect()->back();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }

    /**
     * @param Cancellation $cancellation
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(Cancellation $cancellation): RedirectResponse
    {
        DB::beginTransaction();
        try {
            EditCancellationRepo::run($cancellation, request()->only('comment'));

            // create media
            EditMediaRepo::run(request()->only('media'), $cancellation);

            DB::commit();
            session()->flash('success', flashMessage());
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}