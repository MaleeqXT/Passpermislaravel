<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Services\Monitor\Planning\MonitorPlanningPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitorPlanningPdfController extends Controller
{
    public function download(Request $request, MonitorPlanningPdfService $planningPdf): Response
    {
        $values = $request->validate([
            'date_1' => ['nullable', 'required_with:date_2', 'date_format:Y-m-d'],
            'date_2' => ['nullable', 'required_with:date_1', 'date_format:Y-m-d', 'after_or_equal:date_1'],
        ]);

        $user = $request->user();
        // A direct Moniteur session is always restricted to its own profile.
        // The existing dashboard also lets an administrator inspect a selected
        // Moniteur; retain that established, explicit preview flow only.
        $monitor = $user?->monitor
            ?? Monitor::query()->where('user_id', $user?->id)->first();

        if (! $monitor && $user?->hasAnyRole(['admin', 'super-admin']) && $request->filled('monitor_id')) {
            $targetId = $request->input('monitor_id');
            $monitor = Monitor::query()
                ->whereKey($targetId)
                ->orWhere('user_id', $targetId)
                ->first();
        }

        abort_unless($monitor, 403, 'Compte moniteur requis.');

        $timezone = config('app.timezone') ?: 'UTC';
        $from = filled($values['date_1'] ?? null)
            ? Carbon::createFromFormat('!Y-m-d', $values['date_1'], $timezone)
            : null;
        $to = filled($values['date_2'] ?? null)
            ? Carbon::createFromFormat('!Y-m-d', $values['date_2'], $timezone)
            : null;

        $reservations = Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->when($from && $to, fn ($query) => $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]))
            ->with([
                'training.student.user',
                'training.cancellation',
                'lieu.zone',
            ])
            ->orderBy('date')
            ->orderBy('start_at')
            ->get();

        $filename = $planningPdf->filename($monitor, $from, $to);

        return response($planningPdf->output($monitor, $reservations, $from, $to), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
