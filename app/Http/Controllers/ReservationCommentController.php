<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReservationComment;
use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use Illuminate\Validation\ValidationException;
use Throwable;
class ReservationCommentController extends Controller
{
    //
        public function index(Reservation $reservation)
    {
        return response()->json([
            'data' => $reservation->comments()->with('student.user')->latest()->get(),
        ]);
    }

public function store(Request $request, Reservation $reservation)
{
    try {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'comment'    => 'required|string',
        ]);

        $comment = $reservation->comments()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'data' => $comment,
        ], 201);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

/**
     * Update an existing comment.
     *
     */

public function update(Request $request, Reservation $reservation, ReservationComment $comment)
{
    try {
        $data = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully.',
            'data' => $comment->fresh(),
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    // public function update(Request $request, Reservation $reservation, ReservationComment $comment)
    // {
    //     // Debug: Dump request data

    //     try {
    //         $data = $request->validate([
    //             'comment' => 'required|string|max:1000',
    //         ]);

    //         $comment->update($data);

    //         return redirect()->back()->with('success', 'Comment updated.');
    //     } catch (ValidationException $e) {
    //         return redirect()->back()->withErrors($e->errors())->withInput();
    //     }
    // }

    /**
     * Delete a comment.
     *
     */
public function destroy(Reservation $reservation, ReservationComment $comment)
{
    try {
        if ($comment->reservation_id !== $reservation->id) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found for this reservation.',
            ], 404);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
}


