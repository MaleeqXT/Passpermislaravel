<?php

namespace App\Http\Controllers\V1\Inertia\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\StudentTrainingCancellation;
use App\Notifications\V1\Monitor\Training\Cancellation\CancellationTrainingMonitorNotification;
use App\Notifications\V1\Student\Training\Cancellation\CancellationTrainingStudentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AdminCancellationController2 extends Controller
{
    /** JSON list consumed by the React approval page. */
    public function apiIndex(): JsonResponse
    {
        return response()->json([
            'data' => $this->cancellationRows(),
        ]);
    }

    public function index(): Response
    {
       $cancellations = $this->cancellationRows();

        // Adjust path to match your Vue component location
      return Inertia::render('features/general/approvels/ApprovelsPage', [
            'cancellations' => $cancellations,
        ]);
    }

    private function cancellationRows()
    {
        return StudentTrainingCancellation::with(['student.user'])
    ->latest()
    ->get()
    ->map(fn($cancellation) => [
        'id' => $cancellation->id,
        'student_name' => $cancellation->student?->user?->name ?? 'N/A',
        'hours_requested' => $cancellation->hours_requested,
        // include reservation for debugging/linking if available
        'reservation_id' => $cancellation->reservation_id,
        'status' => $cancellation->status,
        'comment' => $cancellation->comment,
        'created_at' => $cancellation->created_at->format('Y-m-d H:i'),
        'balance' => [
            'rest' => $cancellation->student?->balance ?? 0,
            'used' => $cancellation->student?->used_hours ?? 0,
        ],
    ]);
    }


public function approve(Request $request, $cancellation): JsonResponse
{
        $cancellationModel = StudentTrainingCancellation::findOrFail($cancellation);

        // Validate status
        $validator = Validator::make(['status' => $cancellationModel->status], [
            'status' => 'in:pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => ['status' => 'Cancellation is not in a pending state.'],
            ], 422);
        }

        // Student model fetch
        $student = $cancellationModel->student;
        $reservation = null;
        $monitorUser = null;
        $studentUser = $student?->user;

        // if we received a reservation id, attempt to cancel it and credit wallet properly
        if ($cancellationModel->reservation_id) {
            $reservation = \App\Models\Roles\Monitor\Schedule\Reservation::find($cancellationModel->reservation_id);
            if ($reservation) {
                $monitorUser = $reservation?->monitor?->user;
                // if there is a training record, capture details before deleting
                if ($reservation->training) {
                    $offerId = $reservation->training->offer_id;
                    $hourAmount = (int) ($reservation->hour
                        ?? $reservation->training->reservation?->hour
                        ?? abs($cancellationModel->hours_requested)
                        ?? 1);

                    // delete the training through repo (triggers hooks)
                    \App\Repository\V2\Student\Schedule\Training\Job\DestroyTrainingRepo::run($reservation->training);

                    // credit wallet back to the offer used by the training
                    if ($student && $offerId) {
                        \App\Repository\V2\Student\Schedule\Training\Wallet\IncOrDecWalletRepo::run(
                            $student,
                            $offerId,
                            'inc',
                            $hourAmount
                        );
                    }
                }

                // note: do NOT delete the reservation record itself
                // keeping it in the table allows availability queries (whereDoesntHave('training'))
                // to surface the slot again after the training is removed.
            }
        } else {
            // fallback balance increment
            if ($student) {
                $student->balance += 1;
                $student->save();
            }
        }

        // Update cancellation status
        $cancellationModel->update(['status' => 'approved']);
        $this->sendApprovalNotifications($studentUser, $monitorUser, $reservation);

        $cancellationData = [
            'id' => $cancellationModel->id,
            'student_name' => $cancellationModel->student?->user?->name ?? 'N/A',
            'hours_requested' => $cancellationModel->hours_requested,
            'status' => $cancellationModel->status,
            'comment' => $cancellationModel->comment,
            'created_at' => $cancellationModel->created_at->format('Y-m-d H:i'),
        ];

        return response()->json([
            'cancellation' => $cancellationData,
            'balance' => [
                'rest' => $student?->balance ?? 0,
                'used' => $student?->used_hours ?? 0,
            ],
            'success' => 'Cancellation approved and hour(s) restored.',
        ]);
    }

    public function reject(Request $request, $cancellation): JsonResponse
    {
        $cancellationModel = StudentTrainingCancellation::findOrFail($cancellation);

        $validator = Validator::make(['status' => $cancellationModel->status], [
            'status' => 'in:pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => ['status' => 'Cancellation is not in a pending state.'],
            ], 422);
        }

        $cancellationModel->update(['status' => 'rejected']);

        $cancellationData = [
            'id' => $cancellationModel->id,
            'student_name' => $cancellationModel->student?->user?->name ?? 'N/A',
            'hours_requested' => $cancellationModel->hours_requested,
            'status' => $cancellationModel->status,
            'comment' => $cancellationModel->comment,
            'created_at' => $cancellationModel->created_at->format('Y-m-d H:i'),
        ];

        return response()->json([
            'cancellation' => $cancellationData,
            'success' => 'Cancellation rejected.',
        ]);
    }

    protected function sendApprovalNotifications($studentUser, $monitorUser, $reservation): void
    {
        if (!$reservation) {
            return;
        }

        if ($monitorUser?->email) {
            $this->sendNotificationSafely(
                $monitorUser->email,
                new CancellationTrainingMonitorNotification($monitorUser, $reservation),
                'approval approved monitor'
            );
        }

        if ($studentUser?->email) {
            $this->sendNotificationSafely(
                $studentUser->email,
                new CancellationTrainingStudentNotification($studentUser, $reservation),
                'approval approved student'
            );
        }
    }

    protected function sendNotificationSafely(string $email, object $notification, string $context): void
    {
        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (Throwable $e) {
            Log::error('Approval notification failed', [
                'context' => $context,
                'email' => $email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
