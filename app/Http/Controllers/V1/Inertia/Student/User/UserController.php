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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

    private const AVAILABILITY_DAYS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    private const NOTIFICATION_KEYS = ['courses', 'email', 'sms', 'news'];

    private const DEFAULT_NOTIFICATIONS = ['courses' => true, 'email' => true, 'sms' => true, 'news' => false];

    /** Save the weekly time slots the student is usually available for lessons. */
    public function updateAccountAvailability(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->student, 404, 'Profil élève introuvable.');
        $this->ensureAccountColumn('students', 'weekly_availability');

        $data = $request->validate([
            'availability' => ['present', 'array'],
            'availability.*' => ['array', 'max:6'],
            'availability.*.*.start' => ['required', 'date_format:H:i'],
            'availability.*.*.end' => ['required', 'date_format:H:i'],
        ]);

        $availability = [];
        foreach (self::AVAILABILITY_DAYS as $day) {
            $slots = collect($data['availability'][$day] ?? [])
                ->map(fn ($slot) => ['start' => $slot['start'], 'end' => $slot['end']])
                ->sortBy('start')
                ->values();

            foreach ($slots as $index => $slot) {
                if ($slot['start'] >= $slot['end']) {
                    throw ValidationException::withMessages(['availability' => "L’heure de début doit précéder l’heure de fin ({$day})."]);
                }
                if ($index > 0 && $slot['start'] < $slots[$index - 1]['end']) {
                    throw ValidationException::withMessages(['availability' => "Deux créneaux se chevauchent le {$day}."]);
                }
            }

            $availability[$day] = $slots->all();
        }

        $unknownDays = array_diff(array_keys($data['availability']), self::AVAILABILITY_DAYS);
        if ($unknownDays) {
            throw ValidationException::withMessages(['availability' => 'Jour de disponibilité inconnu.']);
        }

        $user->student->update(['weekly_availability' => $availability]);

        return response()->json([
            'message' => 'Vos disponibilités ont été enregistrées.',
            'data' => $this->accountPayload($user->fresh(), $user->zone_id),
        ]);
    }

    /** Save the student's notification choices. */
    public function updateAccountNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->student, 404, 'Profil élève introuvable.');
        $this->ensureAccountColumn('students', 'notification_preferences');

        $data = $request->validate(collect(self::NOTIFICATION_KEYS)
            ->mapWithKeys(fn ($key) => [$key => ['required', 'boolean']])
            ->all());

        $user->student->update(['notification_preferences' => collect($data)->map(fn ($value) => (bool) $value)->all()]);

        return response()->json([
            'message' => 'Vos préférences de notifications ont été enregistrées.',
            'data' => $this->accountPayload($user->fresh(), $user->zone_id),
        ]);
    }

    /** Change the password after checking the current one; other devices are signed out. */
    public function updateAccountPassword(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->student, 404, 'Profil élève introuvable.');
        $this->ensureNotPreview($request);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ], [
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }
        if (Hash::check($data['password'], (string) $user->password)) {
            throw ValidationException::withMessages(['password' => 'Le nouveau mot de passe doit être différent de l’actuel.']);
        }

        DB::transaction(function () use ($user, $data, $request) {
            $user->password = $data['password'];
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $user->password_changed_at = now();
            }
            $user->save();

            $currentId = $this->currentTokenId($request);
            $user->tokens()->when($currentId, fn ($query) => $query->whereKeyNot($currentId))->delete();
        });

        return response()->json([
            'message' => 'Votre mot de passe a été modifié. Vos autres appareils ont été déconnectés.',
            'data' => $this->accountPayload($user->fresh(), $user->zone_id),
        ]);
    }

    /** Devices currently signed in to this account (one API token per login). */
    public function accountSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->student, 404, 'Profil élève introuvable.');
        $currentId = $this->currentTokenId($request);

        $sessions = $this->activeTokens($user)
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'created_at', 'last_used_at', 'expires_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'created_at' => $token->created_at?->toIso8601String(),
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'current' => (string) $token->id === (string) $currentId,
            ]);

        return response()->json(['data' => $sessions]);
    }

    /** Sign out one device, or every device except this one when no id is given. */
    public function revokeAccountSessions(Request $request, ?string $token = null): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->student, 404, 'Profil élève introuvable.');
        $this->ensureNotPreview($request);
        $currentId = $this->currentTokenId($request);

        $query = $this->activeTokens($user)->when($currentId, fn ($tokens) => $tokens->whereKeyNot($currentId));
        if ($token !== null) {
            $query->whereKey($token);
        }
        $revoked = $query->delete();

        return response()->json([
            'message' => $revoked ? 'Session déconnectée.' : 'Aucune autre session à déconnecter.',
            'data' => ['revoked' => $revoked],
        ]);
    }

    private function activeTokens(User $user)
    {
        return $user->tokens()
            ->where('name', '!=', 'student-dashboard-preview')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    private function currentTokenId(Request $request): ?string
    {
        $token = $request->user()?->currentAccessToken();

        return $token && isset($token->id) ? (string) $token->id : null;
    }

    /** Staff previewing a student's dashboard must never change that student's credentials. */
    private function ensureNotPreview(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();
        abort_if($token && ($token->name ?? null) === 'student-dashboard-preview', 403, 'Action indisponible en mode aperçu.');
    }

    private function ensureAccountColumn(string $table, string $column): void
    {
        abort_unless(Schema::hasColumn($table, $column), 503, 'Cette fonctionnalité nécessite une mise à jour de la base de données.');
    }

    private function codeValidUntil(Student $student): ?string
    {
        if ($student->date_expiration_code) {
            return Carbon::parse($student->date_expiration_code)->toDateString();
        }

        // The theory test (code) result is valid for 5 years from the date it was obtained.
        return $student->date_code ? Carbon::parse($student->date_code)->addYears(5)->toDateString() : null;
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
                'neph' => $student->neph,
                'boite_type' => $student->boite_type,
                'created_at' => $student->created_at,
                'date_expiration_code' => $student->date_expiration_code,
                'date_code' => $student->date_code,
                'code_valid_until' => $this->codeValidUntil($student),
                'preferred_monitor_id' => $student->preferred_monitor_id,
                'app_language' => $student->app_language,
                'communication_preferences' => $student->communication_preferences ?? [],
                'weekly_availability' => is_array($student->weekly_availability) ? $student->weekly_availability : null,
                'notification_preferences' => array_merge(self::DEFAULT_NOTIFICATIONS, array_intersect_key(
                    is_array($student->notification_preferences) ? $student->notification_preferences : [],
                    self::DEFAULT_NOTIFICATIONS,
                )),
            ],
            'security' => [
                'password_changed_at' => Schema::hasColumn('users', 'password_changed_at') ? $user->password_changed_at : null,
                'two_factor_enabled' => (bool) $user->two_factor_confirmed_at,
                'active_sessions' => $this->activeTokens($user)->count(),
            ],
            'settings_available' => Schema::hasColumn('students', 'weekly_availability'),
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
