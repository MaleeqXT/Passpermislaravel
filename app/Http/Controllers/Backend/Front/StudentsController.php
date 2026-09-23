<?php

namespace App\Http\Controllers\Backend\Front;

use App\Enums\V2\Admin\User\UserRolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\User\Info\RegisterStudentRequest;
use App\Models\User;
use App\Repository\User\StoreUser;
use App\Repository\V2\Student\User\StoreStudentRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentsController extends Controller
{
    /**
     * @return Response
     */
    public function create(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/auth/RegisterStudentPage');
    }

    /**
     * @param StoreUser $createUser
     * @param User $user
     * @return RedirectResponse
     * @throws Exception
     */
  public function store(StoreUser $createUser, StoreStudentRepo $createStudent, RegisterStudentRequest $request): RedirectResponse|JsonResponse
{
    DB::beginTransaction();
    try {
        // create User
        $user = $createUser::run($request->validated() + ['role' => UserRolesEnum::STUDENT->value]);

        // create Student
        $dataFromRequest = $request->only(['neph', 'neph_status', 'date_code', 'how_know', 'boite_type']);
        $student = $createStudent::run(['user_id' => $user->id] + $dataFromRequest);

        // Registration documents are identity documents. Keep them on the
        // private disk; the admin download endpoint authorizes every view.
        $documents = [];
        foreach ($request->input('documents', []) as $index => $document) {
            foreach ($request->file("documents.{$index}.files", []) as $file) {
                $path = $file->store("student-documents/{$student->id}", 'local');
                $documents[$document['type']][] = [
                    'path' => $path,
                    'disk' => 'local',
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }
        if ($documents) {
            $student->required_documents = $documents;
            $student->save();
        }

        DB::commit();
        session()->flash('success', 'Votre compte a été créé avec succès');

        Inertia::setRootView('espace-client');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Votre inscription a été enregistrée.', 'student_id' => $student->id], 201);
        }

        // ✅ Redirect to student dashboard
        return redirect()->route('student.dashboard.index');

    } catch (Exception $e) {
        DB::rollBack();
        session()->flash('error', 'Une erreur est survenue lors de la création du compte');
        throw $e;
    }
}

}
