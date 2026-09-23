<?php

namespace App\Http\Controllers\V1\Inertia\Student\User;


use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\User\Info\StoreStudentRequest;
use App\Http\Requests\V1\Student\User\Info\UpdateStudentRequest;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Notifications\V1\Student\Welcome\WelcomeStudentNotification;
use App\Repository\User\DestroyUser;
use App\Repository\User\EditUser;
use App\Repository\User\StoreUser;
use App\Repository\V2\Monitor\Evaluation\FetchDocumentEvaluationRepo;
use App\Repository\V2\Student\Account\V3\Cpf\FetchAllCpfRepo;
use App\Repository\V2\Student\Schedule\Training\Job\FetchPrimaryDataOfStudentTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\FetchAllStudentWalletRepo;
use App\Repository\V2\Student\User\EditStudentRepo;
use App\Repository\V2\Student\User\FetchAllStudentRepo;
use App\Repository\V2\Student\User\FetchStudentRepo;
use App\Repository\V2\Student\User\StoreStudentRepo;
use App\Support\CandidateZoneResolver;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Http\JsonResponse as ResponseJson;
use Throwable;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

class StudentUserController extends Controller
{
    /**
     * @param FetchAllStudentRepo $action
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */


        // public function index()
        // {
        //      $user = auth()->user();
        //         $activeCount = Monitor::whereHas('user', function ($q) use ($user) {
        //         $q->where('zone_id', $user->zone_id)
        //         ->where('status', 1);
        //     })->count();

        //     $archiveCount = Monitor::whereHas('user', function ($q) use ($user) {
        //         $q->where('zone_id', $user->zone_id)
        //         ->where('status', 2);
        //     })->count();

        //     $inhold= Monitor::whereHas('user', function ($q) use ($user) {
        //         $q->where('zone_id', $user->zone_id)
        //         ->where('status', 3);
        //     })->count();

        //     $countAll= Monitor::whereHas('user', function ($q) use ($user) {
        //         $q->where('zone_id', $user->zone_id);
        //     })->count();
        //     // return response()->json("hellooo hello");
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Monitors fetched successfully',
        //         'data' => FetchAllMonitorRepo::run(request()->all(),$user->zone_id),
        //         // 'active_count'=>Monitor::where('status',1)->count(),
        //         // 'inactive_count'=>Monitor::where('status',2)->count(),
        //         // 'inhold_count'=>Monitor::where('status',3)->count(),
        //         'active_count' => $activeCount,

        //         'inactive_count' => $archiveCount,

        //         'inhold_count' =>$inhold,
        //         'all_count' => $countAll,
        //     ], 200);


    //           return response()->json([
    //     'success' => true,
    //      'active_count'  => Secretary::where('status', 1)->count(),  // ✅
    //     'archive_count' => Secretary::where('status', 2)->count(),  // ✅

    //     'data' => $action::run(request()->all()),
    // ]);
        // }


    public function index(Request $request, FetchAllStudentRepo $action)
    {
        $user = auth()->user();
        $selectedZoneIds = array_values(array_filter((array) $request->input('zone_id', $user->zone_id)));
        $baseQuery = User::query()->isStudent()
            ->when($selectedZoneIds !== [], fn ($query) => $query->whereIn('zone_id', $selectedZoneIds));

        $activeCount = (clone $baseQuery)->where('status', 1)->count();
        $inactiveCount = (clone $baseQuery)->where('status', 2)->count();
        $newCount = (clone $baseQuery)->whereDoesntHave('student.wallets')->count();
        $allCount = (clone $baseQuery)->count();
        $students = $action::run(
            $request->all(),
            $selectedZoneIds,
            ['student.zones.lieux', 'student.zones.zips', 'student.offers'],
        );

        // Inertia page visits must receive an Inertia response. The API route
        // remains available for clients that require the JSON data.
        if (! $request->is('api/*')) {
            return Inertia::render('features/roles/students/StudentsPage', [
                'users' => $students,
                'active_count' => $activeCount,
                'inactive_count' => $inactiveCount,
                'new_count' => $newCount,
                'all_count' => $allCount,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Students fetched successfully',
            'data' => $students,
            'active_count' => $activeCount,
            'inactive_count' => $inactiveCount,
            'new_count' => $newCount,
            'all_count' => $allCount,
        ]);
    }

    /**
     * CPF administration table data. Only students marked as CPF are returned,
     * together with their user, CPF offer/documents, wallet offers and trainings.
     */
    public function cpfFormStudents(Request $request): ResponseJson
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $zoneId = auth()->user()?->zone_id;

        $students = Student::query()
            ->with([
                'user:id,name,first_name,last_name,email,phone,zone_id',
                'wallets.offer:id,name,is_cpf,color',
                'trainings.reservation:id,hour,date,start_at,end_at',
                'cpf.offer:id,name,is_cpf,color',
                'cpf.documentQuestionnaireEntreFormation',
                'cpf.documentAttestationHonneur',
                'cpf.documentSuiviPro',
            ])
            ->where('is_cpf', true)
            ->when($zoneId, fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('zone_id', $zoneId)))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->string('search')->trim() . '%';
                $query->whereHas('user', fn ($userQuery) => $userQuery
                    ->where('name', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            })
            ->latest()
            ->paginate($perPage);

        return response()->json($students);
    }
//     public function index(FetchAllStudentRepo $action): Response
// {
//     return Inertia::render(
//         'features/roles/students/StudentsPage',
//         [
//             'users' => $action::run(
//                 request()->all(),
//                 [
//                     'student.zones.lieux',
//                     'student.zones.zips',
//                     'student.offers' // 👈 add this
//                 ]
//             ),
//         ]
//     );
// }




    /**
     * @return Response
     */
    public function create(): Response
    {

        return Inertia::render('features/roles/students/StudentCreatePage');
    }

    /**
     * @param StoreUser $createUser
     * @param StoreStudentRepo $createEleve
     * @param StoreStudentRequest $request
     * @return RedirectResponse
     * @throws Exception
     */

    public function store(
    StoreUser $createUser,
    StoreStudentRepo $createEleve,
    StoreStudentRequest $request
)
{

    
    // dd($request->validated());   ` 
    DB::beginTransaction();

    try {

        
        $mediaPath = null;
        if ($request->hasFile('media')) {
            $mediaPath = $request->file('media')->store('media', 'public');
        }

        $user = $createUser::run(array_merge($request->validated(), [
            'role' => 'student',
            'media' => $mediaPath,
            'zone_id' => CandidateZoneResolver::forVille($request->input('ville')),
        ]));

        $user->assignRole('student');




        // $user = $createUser::run($request->validated());

        $dataFromRequest = $request->only([
            'neph',
            'is_cpf',
            'boite_type',
            'date_code',
            'date_expiration_code',
            'date_expiration_formula',
            'status',
            'how_know',
            'estimation', 
            'balance'
        ]);

        $student = $createEleve::run([
            'user_id' => $user->id
        ] + $dataFromRequest);

        if ($user->email) {
            $this->sendNotificationSafely(
                $user->email,
                new WelcomeStudentNotification($user),
                'student welcome'
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "L'Candidats est bien Ajouter",
            'data'=>[
            'user' => $user,
            'student' => $student,
            ]
        ], 201);

    } catch (Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la création du User',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    // public function store(StoreUser $createUser, StoreStudentRepo $createEleve, StoreStudentRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {





    //         $user = $createUser::run($request->validated());
    //         $dataFromRequest = $request->only(['neph', 'is_cpf', 'boite_type', 'date_code', 'status', 'how_know', 'estimation', 'balance']);
    //         $user_id = $user->id;

    //         $createEleve::run(['user_id' => $user_id] + $dataFromRequest);

    //         if ($user->email) {
    //             $this->sendNotificationSafely(
    //                 $user->email,
    //                 new WelcomeStudentNotification($user),
    //                 'student welcome'
    //             );
    //         }

    //         DB::commit();
    //         session()->flash('success', 'L\'Candidats est bien Ajouter');


    //         return redirect()->route('admin.users.students.index');
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', 'Une erreur est survenue lors de la création du User');
    //         throw $e;
    //     }
    // }

    /**
     * @param Student $student
     * @param FetchStudentRepo $action
      * @param FetchPrimaryDataOfStudentTrainingRepo $contractEleveAction
     * @return Response
     */
    // GetContractEleveAction $contractEleveAction
    // public function edit(Student $student, FetchStudentRepo $action): Response
    // {

    //     return Inertia::render('features/roles/students/views/StudentEditView', [
    //         'user' => $action::run(request()->all(), $student->user_id),
    //         // 'isShowContract' => $contractEleveAction::run($student)->exists(),

    //     ]);
    // }

    public function edit(Request $request, Student $student, FetchStudentRepo $action)
    {
        $user = $action::run(
            $request->all(),
            $student->user_id,
            ['student.trainings.reservation']
        );

        if (! $request->is('api/*')) {
            return Inertia::render('features/roles/students/views/StudentEditView', [
                'user' => $user,
            ]);
        }

        return response()->json(['user' => $user]);
    }

    /**
     * @param Student $student
     * @param EditUser $updateUser
     * @param EditStudentRepo $updateEleve
     * @param UpdateStudentRequest $request
     * @return RedirectResponse
     * @throws Exception
     */



public function update(
    Student $student,
    EditUser $updateUser,
    EditStudentRepo $updateEleve,
     UpdateStudentRequest $request
    // Request $request
) {
    
    DB::beginTransaction();

    try {
        $userData = $request->validated();
        $userData['zone_id'] = CandidateZoneResolver::forVille(
            $request->input('ville', $student->user->ville)
        );

        if ($request->hasFile('media')) {

            // Optional: delete old file
            if ($student->user->media) {
                Storage::disk('public')->delete($student->user->media);
            }

            $userData['media'] = $request->file('media')->store('media', 'public');
        }

        $updateUser::run($student->user_id, $userData);

        $data = $request->only([
            'neph',
            'is_cpf',
            'boite_type',
            'date_code',
            'date_expiration_code',
            'date_expiration_formula',
            'how_know',
            'balance',
            'estimation',
            'memo',
            'memo_color',
            'contract_path'
        ]);

        $updateEleve::run($student, $data);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "L'eleve est bien modifié",
            'data' => $student->fresh(['user'])
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => "Une erreur est survenue lors de la modification du User",
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * @param User $user
     * @param DestroyUser $deleteUser
     * @return RedirectResponse
     * @throws Exception
     */
public function destroy(Student $student):ResponseJson
{
    $user = $student->user;
    if ($user) {
        $user->tokens()->delete();
        $user->deleteProfilePhoto();
        $user->forceDelete();
    }
    $student->forceDelete();

    return response()->json(['success' => true]);
}

    public function getBalanceAndZones(Student $student, FetchAllStudentWalletRepo $walletsByEleveAction, FetchStudentRepo $getStudent)
    {

        return Inertia::render('features/roles/students/views/StudentBalanceView', [
            'balances' => $walletsByEleveAction::run($student, request()->all()),
            'user' => $getStudent::run(request()->all(), $student->user_id)
        ]);
    }

    public function getCart(Student $student, FetchStudentRepo $getStudent)
    {

        return Inertia::render('features/roles/students/views/StudentCartView', [
            'user' => $getStudent::run(request()->all(), $student->user_id, ['student.reviewMonitor'])
        ]);
    }

    /**
     * Display a contract view for the student.
     *
     * @param Student $student
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function contract(Student $student)
    {
        $student->load('user');

        return view('pdf.normal.contract-formation-html', [
            'student' => $student,
            'isAdmin' => true,
        ]);
    }

    public function getCompetences(Student $student, FetchStudentRepo $getStudent)
    {


        return Inertia::render(
            'features/roles/students/views/StudentCompetencyView',
            [
                'user' => $getStudent::run(request()->all(), $student->user_id)
            ]
        );
    }


public function impersonateStart(User $user)
    {
        // Allow both admin and secretary roles to impersonate users
        if (!auth()->user()?->hasAnyRole(['admin', 'secretary'])) {
            session()->flash('error', 'Vous n\'avez pas les droits pour effectuer cette action');
            return redirect()->back();
        }

        $impersonator = auth()->user();

        // keep existing session key for compatibility with front-end (adminUser)
        session()->put('adminUser', $impersonator);
        // also store the impersonator role so we can redirect back correctly on stop
        $impersonatorRole = $impersonator->hasRole('secretary') ? 'secretary' : 'admin';
        session()->put('impersonator_role', $impersonatorRole);

        $impersonator->impersonate($user);

    // Redirect to the role-dispatch entry point so the impersonated user
    // is routed according to their own role (student/monitor/admin).
    // We keep the impersonator role in session so stopping impersonation
    // can redirect back to the impersonator's dashboard correctly.
    return redirect('/admin');
    }


public function impersonateStop()
    {
        auth()->user()->impersonate()->leave();

        // determine where to redirect back based on stored impersonator role
        $role = session()->get('impersonator_role', 'admin');

        session()->forget('adminUser');
        session()->forget('impersonator_role');

        return $role === 'secretary' ? redirect('/secretary/dashboard') : redirect('/admin');
    }


    /**
     * @param Student $student
     * @param FetchAllCpfRepo $action
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCpf(Student $student, FetchAllCpfRepo $action): Response
    {

        return Inertia::render(
            'features/roles/students/views/StudentCPFView',
            [
                'cpfs' => $action::run(request()->all(), $student, ['offer:id,name']),
                'student' => $student
            ]
        );
    }

    /**
     * @param Student $student
     * @param FetchAllCpfRepo $action
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function docs(Student $student, FetchDocumentEvaluationRepo $action, FetchPrimaryDataOfStudentTrainingRepo $contractStudentAction): Response
    {
        return Inertia::render(
            'features/roles/students/views/StudentDocsView',
            [
                'evaluation' => $action::run(['student_id' => $student->id]),
                'user' => FetchStudentRepo::run(request()->all(), $student->user_id),
                'studentContract' => $contractStudentAction::run(['student_id' => $student->id]),

            ]
        );
    }

    protected function sendNotificationSafely(string $email, object $notification, string $context): void
    {
        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (Throwable $e) {
            Log::error('Student user notification failed', [
                'context' => $context,
                'email' => $email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
