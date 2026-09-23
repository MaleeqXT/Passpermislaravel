<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchByMonthAllReservationRepo
{
    /**
     * @param array $attributes
     * @return Collection|array
     */
    public static function run(array $attributes): array|Collection
{
    $zoneId = $attributes['zone_id'] ?? auth()->user()->zone_id;

    return Reservation::query()
        ->with([
            'training.student' => function ($query) {
                $query->realiseHours();
            },
            'training.student.user:id,name,media',
            'training.cancellation',
            'monitor.user:id,name,media',
            'training.offer:id,name,is_cpf,type_offre,color',
            'lieu.zone'
        ])
        ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef, Hour(start_at) as date_hour'))
        ->whereRelation('lieu', 'zone_id', $zoneId)   // <-- hamesha auth user ki zone se filter
        ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyDateRangeFilter($query, $attributes))
        ->when(isset($attributes['monitor_id']), fn($query) => self::applyMonitorFilter($query, $attributes))
        ->when(isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
        ->when(isset($attributes['disp']), fn($query) => self::applyDispFilter($query, $attributes))
        ->when(isset($attributes['session_type']), fn($query) => self::applyTrainingTypeFilter($query, $attributes))
        ->when(isset($attributes['prestation']), fn($query) => self::applyPrestationFilter($query, $attributes))
        ->when(array_key_exists('is_unrestricted', $attributes), fn($query) => $query->where('is_unrestricted', $attributes['is_unrestricted']))
        ->when(isset($attributes['lieu_id']), fn($query) => self::applyLieuFilter($query, $attributes))
        ->orderBy('monitor_id')
        ->get()
        ->groupBy('datef')
        ->map(function ($reservations) {
            return $reservations->map(function ($reservation) {
                if ($reservation->training) {
                    $walletBalance = DB::table('wallets')
                        ->where('student_id', $reservation->training->student_id)
                        ->where('offer_id', $reservation->training->offer_id)
                        ->sum('balance');

                    $reservation->training->wallet_balance = (int) $walletBalance;
                }
                return $reservation;
            });
        })->toArray();
}

//     public static function run(array $attributes): array|Collection
//     {
//         $a=auth()->user()->zone_id;
    
//         return Reservation::query()->where()
//             ->with([
//                 'training.student' => function ($query) {
//                     $query->realiseHours();
//                 },
//                 'training.student.user:id,name,media',
//                 'training.cancellation',
//                 'monitor.user:id,name,media',
//                 'training.offer:id,name,is_cpf,type_offre,color',
//                 'lieu.zone'
//             ])
//             ->select('*', DB::raw('DAY(date) as day ,DATE(date) as datef,  Hour(start_at) as date_hour'))
//             ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyDateRangeFilter($query, $attributes))
//             ->when(isset($attributes['monitor_id']), fn($query) => self::applyMonitorFilter($query, $attributes))
//             ->when(isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
//             ->when(isset($attributes['disp']), fn($query) => self::applyDispFilter($query, $attributes))
//             ->when(isset($attributes['zone_id']), fn($query) => self::applyZoneFilter($query, $attributes))
//                 ->when(array_key_exists('is_unrestricted', $attributes), fn($query) => $query->where('is_unrestricted', $attributes['is_unrestricted']))
//             ->when(isset($attributes['lieu_id']), fn($query) => self::applyLieuFilter($query, $attributes))
//             ->orderBy('monitor_id')
//             ->get()
// ->groupBy('datef')
// ->map(function ($reservations) {

//     return $reservations->map(function ($reservation) {

//         if ($reservation->training) {

//             $walletBalance = DB::table('wallets')
//                 ->where('student_id', $reservation->training->student_id)
//                 ->where('offer_id', $reservation->training->offer_id)
//                 ->sum('balance');

//             $reservation->training->wallet_balance = (int) $walletBalance;
//         }

//         return $reservation;
//     });

// });

//     }

    /**
     * Apply date range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereBetween('date', [
            $attributes['start'] ?? now()->subMonth()->lastOfMonth(CarbonInterface::MONDAY),
            $attributes['end'] ?? now()->addMonth()->firstOfMonth(CarbonInterface::SUNDAY),
        ]);
    }

    /**
     * Apply monitor filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyMonitorFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereIn('monitor_id', $attributes['monitor_id']);
    }

    /**
     * Apply student filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('training', fn($query) => $query->whereIn('student_id', $attributes['student_id']));
    }

    /**
     * Apply disp filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
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

    /**
     * Apply zone filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyZoneFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']);
    }

    /**
     * Apply lieu filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyLieuFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('lieu_id', $attributes['lieu_id']);
    }
}
