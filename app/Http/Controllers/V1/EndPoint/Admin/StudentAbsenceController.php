<?php

namespace App\Http\Controllers\V1\EndPoint\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAbsenceController extends Controller
{
    /**
     * Read-only absence register. An absence is stored by the monitor on the
     * lesson review itself; this endpoint intentionally creates no duplicate
     * absence record.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        // User eager-loads monitor/student/secretary and ReviewMonitor/Training
        // eager-load further relations by default. The register only needs
        // names, so switch those defaults off to keep memory bounded.
        $lightUser = fn ($user) => $user->without(['monitor', 'student', 'secretary']);

        $query = ReviewMonitor::query()
            ->without('monitor')
            ->with([
                'reservation:id,date,start_at,end_at,monitor_id,lieu_id',
                'reservation.training' => fn ($training) => $training->without('cancellation'),
                'reservation.training.student',
                'reservation.training.student.user' => fn ($user) => $lightUser($user)->select(['id', 'name', 'first_name', 'last_name', 'phone', 'zone_id']),
                'reservation.monitor',
                'reservation.monitor.user' => fn ($user) => $lightUser($user)->select(['id', 'name', 'first_name', 'last_name']),
                'reservation.lieu:id,name',
            ])
            ->where('is_absent', true)
            ->whereHas('reservation.training');

        if ($actor?->hasRole('monitor')) {
            $monitorId = $actor->monitor?->id;
            $query->whereHas('reservation', fn ($reservation) => $reservation->where('monitor_id', $monitorId));
        } elseif ($actor?->zone_id) {
            $query->whereHas('reservation.training.student.user', fn ($user) => $user->where('zone_id', $actor->zone_id));
        }

        if (! empty($filters['from'])) {
            $query->whereHas('reservation', fn ($reservation) => $reservation->whereDate('date', '>=', $filters['from']));
        }
        if (! empty($filters['to'])) {
            $query->whereHas('reservation', fn ($reservation) => $reservation->whereDate('date', '<=', $filters['to']));
        }
        if (filled($filters['search'] ?? null)) {
            $term = trim($filters['search']);
            $query->where(function ($reviews) use ($term) {
                $reviews->whereHas('reservation.training.student.user', fn ($user) => $user
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%"))
                    ->orWhereHas('reservation.monitor.user', fn ($user) => $user
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%"));
            });
        }

        // Loading the full history in one response exhausted PHP memory, so
        // the register is paginated (newest lessons first).
        $page = $query
            ->join('reservations', 'review_monitors.reservation_id', '=', 'reservations.id')
            ->select('review_monitors.*')
            ->orderByDesc('reservations.date')
            ->orderByDesc('reservations.start_at')
            ->paginate((int) ($filters['per_page'] ?? 25));

        return response()->json([
            'data' => $page->getCollection()->map(fn (ReviewMonitor $review) => $this->serialize($review))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    private function serialize(ReviewMonitor $review): array
    {
        $reservation = $review->reservation;
        $studentUser = $reservation?->training?->student?->user;
        $monitorUser = $reservation?->monitor?->user;

        return [
            'id' => $review->id,
            'student' => $this->personName($studentUser) ?: 'Élève',
            'phone' => $studentUser?->phone ?: '',
            'monitor' => $this->personName($monitorUser) ?: '—',
            'date' => $reservation?->date?->format('Y-m-d'),
            'start_time' => $reservation?->start_at?->format('H:i'),
            'end_time' => $reservation?->end_at?->format('H:i'),
            'location' => $reservation?->lieu?->name ?: '—',
            'comment' => $review->comment ?: '',
            'recorded_at' => $review->updated_at?->toIso8601String(),
        ];
    }

    private function personName(mixed $user): string
    {
        return trim(implode(' ', array_filter([$user?->first_name, $user?->last_name]))) ?: (string) ($user?->name ?? '');
    }
}
