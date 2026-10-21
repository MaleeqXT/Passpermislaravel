<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\User\Monitor;
use App\Services\Monitor\Planning\MonitorCalendarSubscriptionService;
use App\Services\Monitor\Planning\MonitorPlanningCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitorPlanningCalendarController extends Controller
{
    public function download(Request $request, MonitorPlanningCalendarService $calendar): Response
    {
        $values = $request->validate([
            'date_1' => ['required', 'date_format:Y-m-d'],
            'date_2' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_1'],
        ]);
        $monitor = $this->monitorFor($request);
        $from = Carbon::createFromFormat('!Y-m-d', $values['date_1'], MonitorPlanningCalendarService::TIMEZONE);
        $to = Carbon::createFromFormat('!Y-m-d', $values['date_2'], MonitorPlanningCalendarService::TIMEZONE);

        return $this->calendarResponse(
            $calendar->output($monitor, $calendar->reservations($monitor, $from, $to)),
            $calendar->filename($monitor, $from, $to),
        );
    }

    public function subscription(Request $request, MonitorCalendarSubscriptionService $subscriptions)
    {
        $subscription = $subscriptions->activeFor($this->monitorFor($request));

        return response()->json(['data' => $subscriptions->payload($subscription)]);
    }

    public function regenerate(Request $request, MonitorCalendarSubscriptionService $subscriptions)
    {
        $subscription = $subscriptions->regenerateFor($this->monitorFor($request));

        return response()->json([
            'message' => 'Le lien d’abonnement a été régénéré. Les anciens liens ne fonctionnent plus.',
            'data' => $subscriptions->payload($subscription),
        ]);
    }

    public function revoke(Request $request, MonitorCalendarSubscriptionService $subscriptions): Response
    {
        $subscriptions->revokeFor($this->monitorFor($request));

        return response()->noContent();
    }

    public function feed(string $token, MonitorCalendarSubscriptionService $subscriptions, MonitorPlanningCalendarService $calendar): Response
    {
        $monitor = $subscriptions->monitorForToken($token);
        abort_unless($monitor, 404);

        return response($calendar->output($monitor, $calendar->reservations($monitor)), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="planning-moniteur.ics"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function monitorFor(Request $request): Monitor
    {
        $user = $request->user();
        $monitor = $user?->monitor
            ?? Monitor::query()->where('user_id', $user?->id)->first();

        // Keep the established Planning PDF preview behaviour: a direct
        // Moniteur is always bound to their own profile, while an authorized
        // administrator may explicitly open a selected Moniteur's planning.
        if (! $monitor && $user?->hasAnyRole(['admin', 'super-admin']) && $request->filled('monitor_id')) {
            $targetId = $request->input('monitor_id');
            $monitor = Monitor::query()
                ->whereKey($targetId)
                ->orWhere('user_id', $targetId)
                ->first();
        }

        abort_unless($monitor, 403, 'Compte moniteur requis.');

        return $monitor;
    }

    private function calendarResponse(string $content, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
