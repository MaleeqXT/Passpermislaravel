<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\User\Info\StoreFeedbackStudentRequest;
use App\Http\Resources\Student\Comment\CommentCollection;
use App\Models\Roles\Student\User\Information\StudentNote;
use App\Repository\V2\Student\Account\V3\Feedback\DestroyFeedbackStudentRepo;
use App\Repository\V2\Student\Account\V3\Feedback\EditFeedbackStudentRepo;
use App\Repository\V2\Student\Account\V3\Feedback\FetchAllFeedbackStudentRepo;
use App\Repository\V2\Student\Account\V3\Feedback\StoreFeedbackStudentRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommentStudentController extends Controller
{

    /**
     * @return CommentCollection
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): CommentCollection
    {
        return CommentCollection::make(FetchAllFeedbackStudentRepo::run(request()->all()));
    }


    /**
     * @param StoreFeedbackStudentRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(StoreFeedbackStudentRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // create new Comment
            $res = StoreFeedbackStudentRepo::run($request->validated());

            DB::commit();
            session()->flash('success', 'Le Comment est bien Ajouter');
            // return
            return  response()->json($res);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la création du Comment');
            throw $e;
        }
    }


    /**
     * @param StoreFeedbackStudentRequest $request
     * @param StudentNote $studentNote
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(StoreFeedbackStudentRequest $request, StudentNote $studentNote): JsonResponse
    {
        DB::beginTransaction();
        try {
            $res =  EditFeedbackStudentRepo::run($studentNote, $request->validated());

            DB::commit();
            session()->flash('success', 'Le Comment est bien Modifier');
            return  response()->json($res);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la modification du Comment');
            throw $e;
        }
    }


    /**
     * @param StudentNote $studentNote
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(StudentNote $studentNote): JsonResponse
    {
        DB::beginTransaction();
        try {

            // delete Comment
            $res =  DestroyFeedbackStudentRepo::run($studentNote);

            DB::commit();

            session()->flash('success', 'Le Comment est bien Supprimer');
            return  response()->json($res);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la suppression du Comment');
            throw $e;
        }
    }
}
