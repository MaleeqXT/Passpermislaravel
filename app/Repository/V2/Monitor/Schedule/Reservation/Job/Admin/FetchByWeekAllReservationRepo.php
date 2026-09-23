<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;

class FetchByWeekAllReservationRepo
{
    /**
     * @param array $attributes
     * @return array|Builder|Collection|HigherOrderWhenProxy
     */
public static function run(array $attributes)
{
    $zoneId = $attributes['zone_id'] ?? auth()->user()->zone_id;

    $reservations = Reservation::query()
        ->with([
            'training.student' => fn($query) => $query->realiseHours(),
            'training.student.user:id,name,media',
            'training.cancellation',
            'monitor.user:id,name,media',
            'training.offer:id,name,is_cpf,type_offre,color',
            'lieu.zone',
            'latestComment.student.user:id,name,media',
        ])
        ->select('*', DB::raw('DAY(date) as day ,DATE(date) as datef, HOUR(start_at) as date_hour'))
        ->whereRelation('lieu', 'zone_id', $zoneId)   // <-- yeh naya line, hamesha zone se filter
        ->when(
            isset($attributes['start']) && isset($attributes['end']),
            fn($query) => $query->whereBetween('date', [$attributes['start'], $attributes['end']]),
            fn($query) => self::applyWeekDateFallback($query)
        )
        ->when(isset($attributes['monitor_id']), fn($query) => self::applyMonitorFilter($query, $attributes))
        ->when(isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
        ->when(isset($attributes['disp']), fn($query) => self::applyDispFilter($query, $attributes))
        ->when(isset($attributes['session_type']), fn($query) => self::applyTrainingTypeFilter($query, $attributes))
        ->when(isset($attributes['prestation']), fn($query) => self::applyPrestationFilter($query, $attributes))
        ->when(isset($attributes['lieu_id']), fn($query) => self::applyLieuFilter($query, $attributes))
        ->when(array_key_exists('is_unrestricted', $attributes), fn($query) => $query->where('is_unrestricted', $attributes['is_unrestricted']))
        ->orderBy('monitor_id')
        ->get();

    // One grouped query replaces a wallet query for every reservation.
    $studentIds = $reservations->pluck('training.student_id')->filter()->unique()->values();
    $offerIds = $reservations->pluck('training.offer_id')->filter()->unique()->values();
    $walletBalances = $studentIds->isEmpty() || $offerIds->isEmpty()
        ? collect()
        : DB::table('wallets')
            ->selectRaw('student_id, offer_id, SUM(balance) as balance')
            ->whereIn('student_id', $studentIds)
            ->whereIn('offer_id', $offerIds)
            ->groupBy('student_id', 'offer_id')
            ->get()
            ->keyBy(fn ($wallet) => "{$wallet->student_id}:{$wallet->offer_id}");

    return $reservations
        ->groupBy('datef')
        ->map(function ($dailyReservations) use ($walletBalances) {
            return $dailyReservations->map(function ($reservation) use ($walletBalances) {
                if ($reservation->training) {
                    $key = "{$reservation->training->student_id}:{$reservation->training->offer_id}";
                    $reservation->training->wallet_balance = (int) ($walletBalances->get($key)->balance ?? 0);
                }
                return $reservation;
            });
        })->toArray();
}


    // Helper Methods

    private static function applyWeekDateFallback(Builder $query): Builder
    {
        $startOfWeek = now()->startOfWeek(CarbonInterface::MONDAY);
        $endOfWeek = now()->endOfWeek(CarbonInterface::SUNDAY);
        return $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
    }

    private static function applyMonitorFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereIn('monitor_id', $attributes['monitor_id']);
    }

    private static function applyStudentFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('training', fn($q) => $q->whereIn('student_id', $attributes['student_id']));
    }

    private static function applyDispFilter(Builder $query, array $attributes): Builder
    {
        return $attributes['disp'] === "true"
            ? $query->where('is_active', true)->doesntHave('training.offer')
            : $query->whereHas('training.offer');
    }

    private static function applyTrainingTypeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('training', fn($training) => $training->where('session_type', $attributes['session_type']));
    }

    private static function applyPrestationFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('training', fn($training) => $training->where('prestation', $attributes['prestation']));
    }

    private static function applyLieuFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('lieu_id', $attributes['lieu_id']);
    }

    private static function applyZoneFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']);
    }
}
