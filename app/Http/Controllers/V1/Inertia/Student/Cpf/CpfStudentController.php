<?php

namespace App\Http\Controllers\V1\Inertia\Student\Cpf;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Cpf\DocumentCpfRequest;
use App\Models\Roles\Student\Cpf\Cpf;
use App\Repository\V2\Admin\Cpf\FetchAllCpfRepo;
use App\Repository\V2\Student\Cpf\Document\StoreCPFDocumentRepo;
use App\Repository\V2\Student\Cpf\FetchCpfStudentRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CpfStudentController extends Controller
{

    /**
     *   /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/cpf/CPFPage', [
            'cpfs' => FetchAllCpfRepo::run(request()->all()),
        ]);
    }

    /**
     *   /**
     * @param Cpf $cpf
     * @return Response
     */
    public function view(Cpf $cpf): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/cpf/CPFViewPage', [
            'cpf' => FetchCpfStudentRepo::run($cpf, request()->all()),
        ]);
    }

    /**
     *   /**
     * @param Cpf $cpf
     * @param DocumentCpfRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(Cpf $cpf, DocumentCpfRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {

            StoreCPFDocumentRepo::run($cpf, $request->validated());
            DB::commit();

            session()->flash('success', flashMessage());
            // return

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
