<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ReservationRequest;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Inertia\Response;
use Ramsey\Uuid\Uuid;

class ReservationRequestController extends Controller
{

 public function requests(): Response
{
    $reservationRequests = ReservationRequest::with(['monitor.user'])
        ->orderBy('created_at', 'desc')
        ->get();

    return Inertia::render('features/general/dashboard/MonitorRequests', [
        'reservationRequests' => $reservationRequests
    ]);
}
        public function store(Request $request)
    {
        \Log::debug('Raw request data: ', $request->all());

        $validated = $request->validate([
            'hours_periods' => 'nullable|array', // Changed from required to nullable
            'comment' => 'nullable|string|max:1000',
            'monitor_id' => 'nullable|uuid|exists:monitors,id',
        ]);

        \Log::debug('Validated data: ', $validated);

        try {
            // Get authenticated user's monitor ID
            $user = Auth::user();
            $monitor_id = $validated['monitor_id'] ?? ($user->monitor->id ?? null);

            if (!$monitor_id) {
                \Log::error('No monitor found for user: ' . $user->id);

                if ($request->wantsJson()) {
                    return response()->json(['error' => 'No monitor record found'], 422);
                }
                return redirect()->back()->withErrors(['monitor_id' => 'No monitor record found']);
            }

            // Ensure monitor_id is properly formatted as string
            $monitor_id = (string) $monitor_id;

            // If it's a UUID, ensure it's properly formatted
            if (Uuid::isValid($monitor_id)) {
                $monitor_id = Uuid::fromString($monitor_id)->toString();
            }

            $monitor = Monitor::find($monitor_id);
            if (!$monitor) {
                \Log::error('No monitor found for monitor_id: ' . $monitor_id);

                if ($request->wantsJson()) {
                    return response()->json(['error' => 'No monitor record found for ID ' . $monitor_id], 422);
                }
                return redirect()->back()->withErrors(['monitor_id' => 'No monitor record found for ID ' . $monitor_id]);
            }

            $requestData = DB::transaction(function () use ($validated, $monitor_id) {
                return ReservationRequest::create([
                    'monitor_id' => $monitor_id,
                    'hours_periods' => $validated['hours_periods'] ?? null, // Allow null
                    'comment' => $validated['comment'] ?? null,
                    'status' => 'pending',
                ]);
            });

            \Log::info('Reservation request created successfully: ', $requestData->toArray());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Reservation request created successfully',
                    'data' => $requestData
                ], 201);
            }

            return redirect()->back()->with('success', 'Reservation request created successfully');

        } catch (QueryException $e) {
            \Log::error('Database error: ', ['error' => $e->getMessage(), 'code' => $e->getCode()]);

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Failed to save request: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->withErrors(['message' => 'Failed to save request: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::error('Unexpected error: ', ['error' => $e->getMessage()]);

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Failed to save request'], 500);
            }
            return redirect()->back()->withErrors(['message' => 'Failed to save request']);
        }
    }


    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|in:pending,approved,rejected',
        'comment' => 'nullable|string',
        'hours_periods' => 'nullable|array',
        'hours_periods.*.start' => 'required_with:hours_periods|string',
        'hours_periods.*.end' => 'required_with:hours_periods|string',
    ]);

    $reservation = ReservationRequest::findOrFail($id);

    $reservation->update([
        'status' => $validated['status'],
        'comment' => $validated['comment'] ?? null,
        'hours_periods' => $validated['hours_periods'] ?? null,
    ]);

    return back()->with('success', 'Reservation updated successfully');
}

}
