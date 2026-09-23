<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchByWeekAllAvailableReservationRepo
{
    /**
     * @param array $attributes
     * @return Collection
     */
    public static function run(array $attributes): Collection
    {
        $date1 = isset($attributes['date_1']) ? Carbon::parse($attributes['date_1']) : null;
        $date2 = isset($attributes['date_2']) ? Carbon::parse($attributes['date_2']) : null;

        $reservedReservations = self::getReservedReservations($attributes, $date1, $date2);
        $availableReservations = self::getAvailableReservations($attributes, $date1, $date2, $reservedReservations);

        return $reservedReservations->merge($availableReservations)
            ->sortBy('start_at')
            ->groupBy('datef');
    }

    private static function getReservedReservations(array $attributes, ?Carbon $date1, ?Carbon $date2): Collection
    {
        $studentVille = auth()->user()->student->user->ville ?? null;
        $studentBoiteType = auth()->user()->student->boite_type ?? 0;

        return Reservation::with([
                'training.student.user:id,name,media',
                'monitor.user:id,name,media,phone,ville',
                'training.offer:id,name,type_offre',
                'lieu.zone'
            ])
            ->select('*', DB::raw('DAY(date) as day ,DATE(date) as datef, HOUR(start_at) as date_hour'))
            ->when($date1 && $date2, fn($query) => $query->whereBetween('date', [$date1, $date2]))
            ->whereHas('training.offer')
            ->when(isset($attributes['lieu_id']), fn($query) => self::applyLieuFilter($query, $attributes))
            ->when(auth()->user()->hasRole('student'), function ($query) use ($studentVille, $studentBoiteType) {
                $query
                    ->whereHas('monitor.user', fn($q) => $q->where('ville', $studentVille))
                    ->whereHas('monitor.details', fn($q) => $q->where('is_auto', $studentBoiteType))
                    ->whereHas('training', fn($q) => $q->where('student_id', auth()->user()->student->id));
            })
            ->orderBy('datef')
            ->orderBy('date_hour')
            ->get();
    }

    private static function getAvailableReservations(array $attributes, ?Carbon $date1, ?Carbon $date2, Collection $reservedReservations): Collection
    {
        $now = Carbon::now();
        $studentVille = auth()->user()->student->user->ville ?? null;
        $studentBoiteType = auth()->user()->student->boite_type ?? 0;

        $excludedSlots = $reservedReservations->map(function ($reservation) {
            return [
                'date' => Carbon::parse($reservation->date)->format('Y-m-d'),
                'start_at' => Carbon::parse($reservation->start_at)->format('H:i:s'),
            ];
        })->unique();

        return Reservation::with([
                'monitor.user:id,name,media,phone,ville',
                'lieu.zone'
            ])
            ->select('*', DB::raw('DAY(date) as day ,DATE(date) as datef, HOUR(start_at) as date_hour'))
            ->whereDoesntHave('training')
            ->whereDoesntHave('trainingProposals', function ($query) {
                $query->where('status', '!=', ProposalStatusEnum::CANCELLED->value);
            })
            ->when(
                $date1 && $date2,
                function ($query) use ($date1, $date2, $now) {
                    $query->whereBetween('date', [$date1, $date2]);
                    if ($date1->isBefore($now)) {
                        $query->where(function ($q) use ($now) {
                            $q->whereDate('date', '>', $now->format('Y-m-d'))
                                ->orWhere(function ($q2) use ($now) {
                                    $q2->whereDate('date', '=', $now->format('Y-m-d'))
                                        ->whereTime('start_at', '>=', $now->format('H:i:s'));
                                });
                        });
                    }
                },
                fn($query) => $query->where(function ($q) use ($now) {
                    $q->whereDate('date', '>', $now->format('Y-m-d'))
                        ->orWhere(function ($q2) use ($now) {
                            $q2->whereDate('date', '=', $now->format('Y-m-d'))
                                ->whereTime('start_at', '>=', $now->format('H:i:s'));
                        });
                })
            )
            ->where(function ($query) use ($excludedSlots) {
                foreach ($excludedSlots as $slot) {
                    $query->where(function ($q) use ($slot) {
                        $q->whereDate('date', '!=', $slot['date'])
                          ->orWhereTime('start_at', '!=', $slot['start_at']);
                    });
                }
            })
            ->when(isset($attributes['lieu_id']), fn($query) => self::applyLieuFilter($query, $attributes))
            // The student booking calendar shows every active free monitor
            // availability in the selected week. Location/vehicle filters are
            // user preferences and must not hide otherwise bookable slots.
            ->orderBy('start_at')
            ->get();
    }

    private static function applyLieuFilter(Builder $query, array $attributes): Builder
    {
        $lieuIds = is_array($attributes['lieu_id']) ? $attributes['lieu_id'] : explode(',', $attributes['lieu_id']);
        return $query->whereIn('lieu_id', $lieuIds);
    }
}
