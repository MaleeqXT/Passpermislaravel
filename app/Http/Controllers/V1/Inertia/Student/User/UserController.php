<?php

namespace App\Http\Controllers\V1\Inertia\Student\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UpdatePasswordRequest;
use App\Http\Requests\V1\Student\User\Info\UpdateStudentRequest;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Repository\User\EditUser;
use App\Repository\User\EditUserPasswordAction;
use App\Repository\V2\Student\User\EditStudentRepo;
use App\Repository\V2\Student\User\FetchStudentRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /** Data for the React student-account page. */
    public function accountApi(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->student, 404, 'Profil élève introuvable.');

        $zoneId = $request->query('zone_id', $user->zone_id);

        return response()->json([
            'data' => $this->accountPayload($user, $zoneId),
        ]);
    }

    /** Update the authenticated student's personal information and photo. */
    public function updateAccountPersonal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'phone' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'postal' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'media' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:6024'],
        ]);

        $user = $request->user();
        if ($request->hasFile('media')) {
            $data['media'] = $request->file('media')->store("student-profiles/{$user->student->id}", 'public');
        }

        $user->update($data);

        return response()->json([
            'message' => 'Vos informations personnelles ont été mises à jour.',
            'data' => $this->accountPayload($user->fresh(), $user->zone_id),
        ]);
    }

    /** Update account preferences: zone, BA/BM, preferred instructor and communication choices. */
    public function updateAccountPreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'boite_type' => ['required', 'integer', 'in:0,1'],
            'zone_id' => ['required', 'exists:zones,id'],
            'preferred_monitor_id' => ['nullable', 'exists:monitors,id'],
            'app_language' => ['nullable', 'string', 'max:10'],
            'communication_preferences' => ['nullable', 'array'],
            'communication_preferences.*' => ['string', 'in:Email,SMS,Notifications'],
        ]);

        $user = $request->user();
        $student = $user->student;
        abort_unless($student, 404, 'Profil élève introuvable.');

        if (!empty($data['preferred_monitor_id'])) {
            $monitorIsAvailable = Monitor::query()
                ->whereKey($data['preferred_monitor_id'])
                ->where('status', 1)
                ->whereHas('user', fn ($query) => $query->where('zone_id', $data['zone_id']))
                ->exists();

            if (!$monitorIsAvailable) {
                return response()->json(['message' => "L'enseignant sélectionné n'est pas actif dans cette agence."], 422);
            }
        }

        DB::transaction(function () use ($user, $student, $data) {
            $user->update(['zone_id' => $data['zone_id']]);
            $student->update([
                'boite_type' => $data['boite_type'],
                'preferred_monitor_id' => $data['preferred_monitor_id'] ?? null,
                'app_language' => $data['app_language'] ?? 'fr',
                'communication_preferences' => $data['communication_preferences'] ?? [],
            ]);
        });

        return response()->json([
            'message' => 'Vos préférences ont été mises à jour.',
            'data' => $this->accountPayload($user->fresh(), $data['zone_id']),
        ]);
    }

    private function accountPayload(User $user, ?string $zoneId): array
    {
        $user->load(['zone', 'student.preferredMonitor.user']);
        $student = $user->student;
        $selectedZoneId = $zoneId ?: $user->zone_id;

        $monitors = Monitor::query()
            ->with('user:id,first_name,last_name,name,zone_id')
            ->where('status', 1)
            ->when($selectedZoneId, fn ($query) => $query->whereHas('user', fn ($users) => $users->where('zone_id', $selectedZoneId)))
            ->orderBy('created_at')
            ->get()
            ->map(fn (Monitor $monitor) => [
                'id' => $monitor->id,
                'name' => trim(($monitor->user?->first_name ?? '') . ' ' . ($monitor->user?->last_name ?? '')) ?: $monitor->user?->name,
                'zone_id' => $monitor->user?->zone_id,
            ]);

        return [
            'user' => $user->only(['id', 'first_name', 'last_name', 'name', 'email', 'phone', 'date_naissance', 'adresse', 'postal', 'ville', 'media', 'zone_id', 'profile_photo_url']),
            'student' => [
                'id' => $student->id,
                'boite_type' => $student->boite_type,
                'created_at' => $student->created_at,
                'preferred_monitor_id' => $student->preferred_monitor_id,
                'app_language' => $student->app_language,
                'communication_preferences' => $student->communication_preferences ?? [],
            ],
            'agency' => $user->zone?->only(['id', 'name']),
            'zones' => Zone::query()->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'monitors' => $monitors,
        ];
    }

    /** Return a student's contract to an authenticated student or staff member. */
    public function showContract(Request $request, Student $student)
    {
        $actor = $request->user();
        $isStaff = $actor?->hasAnyRole(['admin', 'super-admin', 'secretary']);

        abort_unless($actor && ($actor->id === $student->user_id || $isStaff), 403);

        $student->load('user');

        return view('pdf.normal.contract-formation-html', [
            'student' => $student,
            'isAdmin' => $isStaff,
        ]);
    }

    /** Store one or more required profile documents in the student's JSON document list. */
    public function storeRequiredDocument(Request $request, Student $student): JsonResponse
    {
        $actor = $request->user();
        $isStaff = $actor?->hasAnyRole(['admin', 'super-admin', 'secretary']);

        abort_unless($actor && ($actor->id === $student->user_id || $isStaff), 403);

        $data = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'replace' => ['nullable', 'boolean'],
            'existing_path' => ['nullable', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:51200'],
        ]);

        $documents = $student->required_documents ?? [];
        $documentType = $data['document_type'];
        $storedFiles = $documents[$documentType] ?? [];

        // Support documents saved by the previous single-file format too.
        if (isset($storedFiles['path'])) {
            $storedFiles = [$storedFiles];
        }

        // A replacement may only remove a path already recorded under this
        // student's selected document type. Never act on an arbitrary path.
        if (($data['replace'] ?? false) && !empty($data['existing_path'])) {
            $oldPath = $data['existing_path'];
            $oldFile = collect($storedFiles)->first(fn ($storedFile) => is_array($storedFile) && ($storedFile['path'] ?? null) === $oldPath);

            if ($oldFile) {
                $storedFiles = array_values(array_filter($storedFiles, fn ($storedFile) => !is_array($storedFile) || ($storedFile['path'] ?? null) !== $oldPath));
                Storage::disk('public')->delete($oldPath);
            }
        }

        foreach ($data['files'] as $file) {
            $path = $file->store("student-documents/{$student->id}", 'public');
            $storedFiles[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        $documents[$documentType] = $storedFiles;

        $student->required_documents = $documents;
        $student->save();

        return response()->json(['required_documents' => $student->required_documents]);
    }

    /**
     * @return Response
     */
    public function index(): Response
   
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/settings/account/ProfilePage', [
            'user' => FetchStudentRepo::run(['user_id' => auth()->user()->id]),
            'hideBottomBar' => true
        ]);
    }


    /**
     * @return Response
     */
    public function passwordChange(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/settings/account/PasswordChangePage', [
            'user' => FetchStudentRepo::run(['user_id' => auth()->user()->id]),
            "hideBottomBar" => true,
        ]);
    }

    /**
     * @param User $user
     * @return Response
     */
    public function edit(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/students/StudentsPage', [
            'user' => FetchStudentRepo::run(['user_id' => auth()->user()->id]),
        ]);
    }

    /**
     * @param Monitor $monitor
     * @param UpdateStudentRequest $monitorRequest
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(Student $student, UpdateStudentRequest $studentRequest): RedirectResponse
    {
        DB::beginTransaction();
        try {
            EditUser::run($student->user_id, $studentRequest->validated());

            EditStudentRepo::run($student, $studentRequest->only('experience', 'dernier_experience', 'details_experience', 'is_auto', 'is_manual'));

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
     * @param Monitor $monitor
     * @param UpdatePasswordRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function updatePassword(Student $student, UpdatePasswordRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {

            EditUserPasswordAction::run($student->user, $request->only('password'));
            DB::commit();
            session()->flash('success', flashMessage());
            // return
            // Inertia::setRootView('espace-student');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
