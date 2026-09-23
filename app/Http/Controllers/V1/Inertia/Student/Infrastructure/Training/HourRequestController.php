<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Schedule\Training\StoreHourRequest;
use App\Models\StudentTrainingCancellation;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class HourRequestController extends Controller
{
    /**
     * Store a new hour adjustment request (student -> admin).
     */
    public function store(StoreHourRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        $student = $user?->student ?? Student::query()->where('user_id', $user?->id)->first();

        // Staff can submit the request while viewing a selected student's dashboard.
        if (!$student && !empty($data['student_id']) && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $student = Student::query()->find($data['student_id']);
        }

        if (!$student) {
            return response()->json(['message' => 'Profil élève introuvable.'], 422);
        }
        $reservation = Reservation::with('training')
            ->findOrFail($data['reservation_id']);

        if (!$reservation->training || $reservation->training->student_id !== $student->id) {
            return redirect()->back()->withErrors([
                'reservation_id' => 'Cette réservation ne peut pas être annulée.',
            ]);
        }

        $data['student_id'] = $student->id;
        $data['status'] = 'pending';
        $data['hours_requested'] = (int) ($reservation->hour ?? $data['hours_requested']);

        // reservation id may have been provided for later processing
        if ($request->filled('reservation_id')) {
            $data['reservation_id'] = $request->input('reservation_id');
        }

        $alreadyPending = StudentTrainingCancellation::query()
            ->where('student_id', $student->id)
            ->where('reservation_id', $reservation->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return response()->json(['message' => 'Une demande d’annulation est déjà en attente pour cette séance.'], 422);
        }

        $cancellation = StudentTrainingCancellation::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $cancellation,
                'message' => 'Votre demande a bien été envoyée au secrétariat.',
            ], 201);
        }

        session()->flash('success', "Votre demande a bien été envoyée au secrétariat.");
        return redirect()->back();
    }
}
