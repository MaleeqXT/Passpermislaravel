<?php

namespace App\Http\Controllers\Backend\Front;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchAllOffreRepo;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CPFController extends Controller
{
    /**
     * @param FetchAllOffreRepo $action
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(FetchAllOffreRepo $action): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/cpf/CPFPage', [
            'offers' => $action::run(request()->all() + ['is_cpf' => true,'is_auto' => false,'type'=>1]),
        ]);
    }


    // @todo : add store cpf form function
    /**
     * @param ?
     * @return Response
     */

    public function store()
    {
        try {
            DB::beginTransaction();
            // $action = new StoreCPFRepo();
            // $action::run(request()->all());

            DB::commit();
            session()->flash('success', 'Votre CPF a été enregistré avec succès');
            return redirect()->back();
            DB::commit();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()->with('error', 'Une erreur est survenue lors de l\'enregistrement de votre CPF.')->withInput();
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return redirect()->back()->with('error', 'Une erreur de réponse HTTP est survenue.')->withInput();
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Une erreur inattendue est survenue.')->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Une erreur générale est survenue.')->withInput();
        }
    }
}
