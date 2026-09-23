<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;


use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\User\Info\StoreOrUpdateCallRequest;
use App\Models\Roles\Student\User\Information\Call;
use App\Repository\V2\Student\Account\V3\Call\EditCallRepo;
use App\Repository\V2\Student\Account\V3\Call\FetchAllCallRepo;
use App\Repository\V2\Student\Account\V3\Call\OrganiseCallRepo;
use App\Repository\V2\Student\Account\V3\Call\RemoveCallRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CallController extends Controller
{

    /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index()
    {
        return response()->json(['availableCalls' => FetchAllCallRepo::run(request()->all())]);
    }


    /**
     * @param OrganiseCallRepo $call
     * @param StoreOrUpdateCallRequest $request
     * @return JsonResponse
     * @throws ValidationException|Exception
     */
    public function store(OrganiseCallRepo $call, StoreOrUpdateCallRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // create new Disponibilité
            $availableCalls = $call::run($request->validated());

            DB::commit();
            session()->flash('success', 'Le Disponibilité est bien Ajouter');
            // return
            return response()->json($availableCalls, 200);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la création du Disponibilité');
            throw $e;
        }
    }


    /**
     * @param StoreOrUpdateCallRequest $request
     * @param Call $call
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(StoreOrUpdateCallRequest $request, Call $call): RedirectResponse
    {
        DB::beginTransaction();
        try {
            EditCallRepo::run($call, $request->validated());

            DB::commit();
            session()->flash('success', 'Le Disponibilité est bien Modifier');

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la modification du Disponibilité');
            throw $e;
        }
    }


    /**
     * @param Call $call
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Call $call): JsonResponse
    {
        DB::beginTransaction();
        try {

            // delete Disponibilité
            $destroyedCall = RemoveCallRepo::run($call);

            DB::commit();

            session()->flash('success', 'Le Disponibilité est bien Supprimer');
            // return
            return response()->json($destroyedCall, 200);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la suppression du Disponibilité');
            throw $e;
        }
    }
}
