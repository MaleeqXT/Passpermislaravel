<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class FetchRandomReservationRepo
{
    /**
     * @param array $attributes
     * @param Student $student
     * @return Reservation|null
     */
    public static function run(array $attributes, Student $student): Reservation|null
    {
        Log::info('🔍 FetchRandomReservationRepo.run() called');
        Log::info('Attributes:', $attributes);
        Log::info('Student:', ['id' => $student->id, 'boite_type' => $student->boite_type, 'ville' => $student->user->ville]);

        $query = Reservation::query()
            ->whereDoesntHave('training')
            ->when(isset($attributes['date']), fn(Builder $query) => $query->whereDate('date', $attributes['date']))
            ->when(getMonitorId($attributes), fn(Builder $query) => $query->where('monitor_id', getMonitorId($attributes)))
            ->when(isset($attributes['start_at']), fn(Builder $query) => $query->where('start_at', $attributes['start_at']))
            ->when(isset($attributes['end_at']), fn(Builder $query) => $query->where('end_at', $attributes['end_at']))
            ->when(isset($attributes['lieu_id']), fn($query) => self::applyLieuFilter($query, $attributes))
            ->when(isset($student), fn(Builder $query) => self::applyStudentFilter($query, $student, $attributes));

        Log::info('Query SQL:', [$query->toSql()]);
        Log::info('Query Bindings:', $query->getBindings());

        $result = $query->first();
        Log::info('Result:', $result ? ['id' => $result->id, 'monitor_id' => $result->monitor_id] : ['result' => 'NO RESULT']);

        return $result;
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
        $lieuIds = is_array($attributes['lieu_id']) ? $attributes['lieu_id'] : explode(',', $attributes['lieu_id']);
        return $query->whereIn('lieu_id', $lieuIds);
    }

    /**
     * Apply the filter for the student and their related conditions.
     *
     * @param Builder $query
     * @param Student $student
     * @param array $attributes
     * @return Builder
     */
   private static function applyStudentFilter(Builder $query, Student $student, array $attributes): Builder
{
    $boiteType = $student?->boite_type ?? 0;
    $studentVille = $student?->user?->ville ?? null;

    return $query
        ->whereHas('monitor.details', fn(Builder $q) =>
            $q->where('is_auto', $boiteType)
        )
        ->whereHas('monitor.user', fn(Builder $q) =>
            $q->where('ville', $studentVille)
        );
}

}
