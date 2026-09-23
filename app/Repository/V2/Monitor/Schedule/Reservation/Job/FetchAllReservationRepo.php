<?php
 
namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;
 
use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
 
class FetchAllReservationRepo
{
    /**
     * @param array $attributes
     * @param array $with
     * @return Collection|Builder
     */
    public static function run(array $attributes, array $with = []): array|Collection
    {
        return Reservation::query()
            ->with(self::applyWithRelations($with))
            ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef, Hour(start_at) as date_hour'))
            ->when(
                isset($attributes['all']),
                fn($query) => $query->where(function ($query) {
                    $query->whereHas('training.student')
                        ->orWhere(function ($query) {
                            // Include unassigned availability slots for the
                            // requested week, including past calendar weeks.
                            $query->whereDoesntHave('training');
                        });
                }),
                fn($query) => $query->whereHas('training.student')
            )
            ->when(
                isset($attributes['date_1']) && isset($attributes['date_2']),
                fn($query) => $query->whereBetween('date', [$attributes['date_1'], $attributes['date_2']])
            )
            ->when(
                isset($attributes['date']),
                fn($query) => $query->whereDate('date', $attributes['date'])
            )
            ->when(
                $monitor_id = getMonitorId($attributes),
                fn($query) => $query->where('monitor_id', $monitor_id)
            )
            ->when(
                isset($attributes['student_id']),
                fn($query) => $query->whereHas('training', fn($query) => $query->where('student_id', $attributes['student_id']))
            )
            ->when(
                isset($attributes['start']) && isset($attributes['end']),
                fn($query) => $query->whereBetween('start_at', [$attributes['start'], $attributes['end']])
            )
            ->when(
                isset($attributes['start_at']) && isset($attributes['end_at']),
                fn($query) => self::applyTimeRangeFilter($query, $attributes)
            )
            ->when(
                isset($attributes['is_active']),
                fn($query) => $query->where('is_active', $attributes['is_active'])
            )
            ->when(
                isset($attributes['is_passed']),
                fn($query) => $query->whereDate('date', '<', now())
            )
            ->when(
                isset($attributes['is_coming']),
                fn($query) => $query->whereDate('date', '>=', now())
            )
            ->orderBy('date')
            ->orderBy('date_hour')
            ->get();
    }
 
    /**
     * Apply common with relations.
     *
     * @param array $with
     * @return array
     */
    private static function applyWithRelations(array $with): array
    {
        return array_merge([
            'training.student.user:id,name,last_name,first_name,media,phone,email',
            'training.student' => fn($query) => $query->realiseHours(),
            'monitor.user:id,name,media',
            'training.offer:id,name,type_offre,balance,color',
            'lieu.zone',
            'reviewMonitor.monitor.user:id,name,media,phone',
            'training.cancellation'
        ], $with);
    }
 
    /**
     * Apply time range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyTimeRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereTime('start_at', '<=', $attributes['start_at'])
            ->where(function ($query) use ($attributes) {
                $query->whereTime('end_at', '>=', $attributes['end_at'])
                    ->orWhere(function ($query) use ($attributes) {
                        $query->whereTime('end_at', '>', $attributes['start_at'])
                            ->whereTime('end_at', '<', $attributes['end_at']);
                    });
            });
    }
}
 
 
