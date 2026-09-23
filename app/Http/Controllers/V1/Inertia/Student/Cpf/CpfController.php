<?php

namespace App\Http\Controllers\V1\Inertia\Student\Cpf;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Cpf\EleveCpf\StoreCpfEleveRequest;
use App\Http\Requests\V1\Student\Cpf\EleveCpf\UpdateCpfEleveRequest;
use App\Models\Roles\Student\Cpf\Cpf;
use App\Models\Roles\Student\User\Student;
use App\Notifications\V1\Student\Cpf\WelcomeCpfNotification;
use App\Repository\V2\Admin\Cpf\DestroyCpfRepo;
use App\Repository\V2\Admin\Cpf\EditCpfRepo;
use App\Repository\V2\Admin\Cpf\FetchAllCpfRepo;
use App\Services\Cpf\CpfInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class CpfController extends Controller
{

    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response
    {

        return Inertia::render('features/general/cpf/CPFPage', [
            'cpfs' => FetchAllCpfRepo::run(request()->all()),
        ]);
    }

    /**
     * @param Cpf $cpf
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Cpf $cpf): RedirectResponse
    {
        DB::beginTransaction();
        try {

            DestroyCpfRepo::run($cpf);

            DB::commit();
            session()->flash('success', flashMessage());


            return redirect()->route('admin.cpf.index');
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @param Cpf $cpf
     * @param UpdateCpfEleveRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(Cpf $cpf, UpdateCpfEleveRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            EditCpfRepo::run($cpf, $request->validated());

            DB::commit();
            session()->flash('success', flashMessage());

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @param CpfInterface $cpf
     * @param StoreCpfEleveRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(CpfInterface $cpf, StoreCpfEleveRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $cpf->create($request->validated());

            // send email
            $user = Student::query()->find($request->get('student_id'))->user;
            Notification::route('mail', $user->email)
                ->notify(new WelcomeCpfNotification($user));


            DB::commit();
            session()->flash('success', flashMessage());
            // return

            return redirect()->route('admin.cpf.index');
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
