<?php

namespace App\Http\Controllers\V1\Inertia\System\Zone;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Lieux\AsyncLieuxMoniteurRequest;
use App\Repository\V2\Admin\Zone\Lieux\FetchAllLieuxByMoniteurRepo;
use App\Repository\V2\Monitor\Zone\SyncLieuxToMonitorRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LieuxMonitorController extends Controller
{

    /**
     *   /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
  public function index(): \Illuminate\Http\JsonResponse
{
    return response()->json([
        'zones' => FetchAllLieuxByMoniteurRepo::run(request()->all()),
        'hideBottomBar' => true,
    ]);
}


    /**
     * @param AsyncLieuxMoniteurRequest $request
     * 
     * @throws Exception
     */
    public function store(AsyncLieuxMoniteurRequest $request)
{
    DB::beginTransaction();

    try {
        SyncLieuxToMonitorRepo::run(
            auth()->user()->monitor,
            $request->validated()
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => flashMessage("stored Successfully"),
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => flashMessage('error'),
            'error' => $e->getMessage(), // production me is line ko hata dena
        ], 500);
    }
}
    // public function store(AsyncLieuxMoniteurRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         SyncLieuxToMonitorRepo::run(auth()->user()->monitor, $request->validated());
    //         DB::commit();

    //         session()->flash('success', flashMessage());


    //         return redirect()->route('monitor.places.index');
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }
}
